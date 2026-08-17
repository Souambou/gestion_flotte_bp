<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\JournalActivite;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controle matinal de la flotte.
 *
 * Chaque matin, un controle d'etat est realise sur chaque vehicule,
 * independamment des reservations et des deplacements du jour.
 */
class ChecklistController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:checklists.remplir')->only(['create', 'store']);
    }

    /** Tableau de bord du jour : ce qui est fait, ce qui reste a faire. */
    public function index(Request $request)
    {
        $jour = $this->jourDemande($request);

        $controles = Checklist::with(['vehicule', 'auteur'])
            ->duJour($jour)
            ->get()
            ->sortBy(fn (Checklist $c) => $c->vehicule?->immatriculation ?? '')
            ->values();

        $restants = Checklist::vehiculesRestants($jour);

        return view('checklists.index', [
            'jour' => $jour,
            'controles' => $controles,
            'restants' => $restants,
            'anomalies' => $controles->sum('nombre_anomalies'),
            'conformiteMoyenne' => $controles->count() ? round($controles->avg('taux_conformite')) : 0,
        ]);
    }

    public function create(Request $request, Vehicule $vehicule)
    {
        $jour = $this->jourDemande($request);

        // Un seul controle par vehicule et par jour : on reprend l'existant.
        $checklist = Checklist::where('vehicule_id', $vehicule->id)
            ->whereDate('date_controle', $jour)
            ->first();

        return view('checklists.create', [
            'vehicule' => $vehicule,
            'jour' => $jour,
            'checklist' => $checklist,
            'rubriques' => config('beninpetro.checklist'),
        ]);
    }

    public function store(Request $request, Vehicule $vehicule)
    {
        $jour = $this->jourDemande($request);
        $rubriques = config('beninpetro.checklist');
        $clesAttendues = collect($rubriques)->flatMap(fn ($points) => array_keys($points))->all();

        $donnees = $request->validate([
            'kilometrage' => ['required', 'integer', 'min:0'],
            'niveau_carburant' => ['required', 'integer', 'min:0', 'max:100'],
            'etat_general' => ['required', 'in:bon,moyen,mauvais'],
            'points' => ['required', 'array'],
            'points.*.statut' => ['required', 'in:conforme,a_surveiller,non_conforme,absent'],
            'points.*.commentaire' => ['nullable', 'string', 'max:300'],
            'anomalies' => ['nullable', 'string', 'max:2000'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
            'signature' => ['nullable', 'string'],
            'photos.*' => ['nullable', 'image', 'max:4096'],
        ], [
            'points.required' => 'Renseignez chaque point de contrôle avant de valider.',
        ]);

        $points = collect($donnees['points'])->only($clesAttendues)->all();

        $photos = [];
        foreach ($request->file('photos', []) as $photo) {
            $photos[] = $photo->store('checklists/'.$vehicule->id, 'public');
        }

        $checklist = Checklist::updateOrCreate(
            ['vehicule_id' => $vehicule->id, 'date_controle' => $jour->toDateString()],
            [
                'user_id' => $request->user()->id,
                'kilometrage' => $donnees['kilometrage'],
                'niveau_carburant' => $donnees['niveau_carburant'],
                'etat_general' => $donnees['etat_general'],
                'points' => $points,
                'photos' => $photos ?: null,
                'anomalies' => $donnees['anomalies'] ?? null,
                'commentaire' => $donnees['commentaire'] ?? null,
                'signature' => $donnees['signature'] ?? null,
                'completee_at' => now(),
            ]
        );

        // Le releve du matin fait foi pour le kilometrage du vehicule.
        if ($donnees['kilometrage'] > $vehicule->kilometrage) {
            $vehicule->update(['kilometrage' => $donnees['kilometrage']]);
        }

        JournalActivite::enregistrer(
            'checklist.completee',
            $checklist,
            "Contrôle matinal du {$jour->format('d/m/Y')} — véhicule {$vehicule->immatriculation}"
        );

        return redirect()->route('checklists.index', ['jour' => $jour->toDateString()])
            ->with('succes', "Contrôle du véhicule {$vehicule->immatriculation} enregistré.");
    }

    public function show(Checklist $checklist)
    {
        return view('checklists.show', [
            'checklist' => $checklist->load(['vehicule', 'auteur']),
            'rubriques' => config('beninpetro.checklist'),
        ]);
    }

    /** Jour de controle demande, borne a aujourd'hui au plus tard. */
    protected function jourDemande(Request $request): Carbon
    {
        try {
            $jour = $request->filled('jour') ? Carbon::parse($request->input('jour')) : today();
        } catch (\Throwable) {
            $jour = today();
        }

        return $jour->greaterThan(today()) ? today() : $jour->startOfDay();
    }
}
