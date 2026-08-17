<?php

namespace App\Http\Controllers;

use App\Models\JournalActivite;
use App\Models\Deplacement;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeplacementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:deplacements.consulter')->only(['index', 'show']);
        $this->middleware('permission:deplacements.gerer')->only(['demarrer', 'cloturer', 'update', 'signalerIncident']);
    }

    public function index(Request $request)
    {
        $deplacements = Deplacement::query()
            ->with(['reservation.demandeur', 'vehicule', 'chauffeur'])
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->when($request->input('q'), function ($q, $terme) {
                $q->where('code', 'like', "%{$terme}%")
                    ->orWhereHas('vehicule', fn ($v) => $v->where('immatriculation', 'like', "%{$terme}%"));
            })
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('deplacements.index', [
            'deplacements' => $deplacements,
            'compteurs' => collect(Deplacement::STATUTS)->mapWithKeys(fn ($l, $c) => [$c => Deplacement::where('statut', $c)->count()]),
        ]);
    }

    public function show(Deplacement $deplacement)
    {
        $deplacement->load(['reservation.demandeur', 'vehicule', 'chauffeur', 'ouvreur', 'cloturateur']);

        return view('deplacements.show', [
            'deplacement' => $deplacement,
            'manquants' => $deplacement->elementsManquants(),
        ]);
    }

    /** Ouverture de déplacement : le vehicule passe en déplacement. */
    public function demarrer(Request $request, Deplacement $deplacement)
    {
        if ($deplacement->statut !== 'planifiee') {
            return back()->with('erreur', 'Ce déplacement a déjà été ouvert.');
        }

        $donnees = $request->validate([
            'km_depart' => ['required', 'integer', 'min:0'],
            'depart_reel_at' => ['nullable', 'date'],
        ], [], ['km_depart' => 'kilométrage au départ']);

        $deplacement->update([
            'km_depart' => $donnees['km_depart'],
            'depart_reel_at' => $donnees['depart_reel_at'] ?? now(),
            'statut' => 'en_cours',
            'ouverte_par' => $request->user()->id,
        ]);

        $deplacement->reservation->update(['statut' => 'en_cours']);
        $deplacement->vehicule->update(['statut' => 'en_deplacement']);
        $deplacement->chauffeur?->update(['statut' => 'en_deplacement']);

        JournalActivite::enregistrer('deplacement.demarre', $deplacement, "Déplacement {$deplacement->code} ouvert");

        return back()->with('succes', 'Déplacement ouvert. Bonne route.');
    }

    /** Cloture de déplacement : bloquee tant que les donnees sont incompletes. */
    public function cloturer(Request $request, Deplacement $deplacement)
    {
        $donnees = $request->validate([
            'km_arrivee' => ['required', 'integer', 'min:'.($deplacement->km_depart ?? 0)],
            'arrivee_reelle_at' => ['nullable', 'date'],
            'carburant_consomme' => ['nullable', 'numeric', 'min:0'],
            'cout_carburant' => ['nullable', 'numeric', 'min:0'],
            'autres_frais' => ['nullable', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ], [
            'km_arrivee.min' => 'Le kilométrage à l\'arrivée ne peut pas être inférieur à celui du départ.',
        ], ['km_arrivee' => 'kilométrage à l\'arrivée']);

        $deplacement->fill(array_merge($donnees, [
            'arrivee_reelle_at' => $donnees['arrivee_reelle_at'] ?? now(),
        ]))->save();

        $manquants = $deplacement->elementsManquants();

        if (! empty($manquants)) {
            throw ValidationException::withMessages([
                'cloture' => 'Clôture bloquée — éléments manquants : '.implode(', ', $manquants).'.',
            ]);
        }

        $deplacement->update([
            'statut' => 'terminee',
            'cloturee_par' => $request->user()->id,
        ]);

        $deplacement->reservation->update(['statut' => 'terminee']);
        $deplacement->vehicule->update([
            'statut' => 'disponible',
            'kilometrage' => max($deplacement->vehicule->kilometrage, $deplacement->km_arrivee),
        ]);
        $deplacement->chauffeur?->update(['statut' => 'disponible']);

        JournalActivite::enregistrer('deplacement.cloture', $deplacement, "Déplacement {$deplacement->code} clôturé");

        return redirect()->route('deplacements.show', $deplacement)
            ->with('succes', 'Déplacement clôturé. Les indicateurs ont été mis à jour.');
    }

    public function signalerIncident(Request $request, Deplacement $deplacement)
    {
        $donnees = $request->validate([
            'observations' => ['required', 'string', 'min:10', 'max:2000'],
        ], [], ['observations' => 'description de l\'incident']);

        $deplacement->update([
            'statut' => 'incident',
            'observations' => trim(($deplacement->observations ? $deplacement->observations."\n" : '').'[Incident '.now()->format('d/m/Y H:i').'] '.$donnees['observations']),
        ]);

        JournalActivite::enregistrer('deplacement.incident', $deplacement, "Incident signalé sur le déplacement {$deplacement->code}");

        return back()->with('succes', 'Incident enregistré. Le responsable de flotte peut désormais traiter le dossier.');
    }
}
