<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Planning de la flotte, ouvert a tous les collaborateurs.
 *
 * Chacun doit pouvoir constater par lui-meme, avant de soumettre une demande,
 * quels vehicules sont libres, sur quels creneaux, et qui a reserve quoi.
 */
class PlanningController extends Controller
{
    /** Plage horaire affichee dans la vue semaine. */
    public const HEURE_DEBUT = 6;
    public const HEURE_FIN = 20;

    public function __construct()
    {
        $this->middleware('permission:planning.consulter');
    }

    public function index(Request $request)
    {
        $vue = in_array($request->input('vue'), ['jour', 'semaine']) ? $request->input('vue') : 'semaine';
        $ancre = $this->ancre($request);

        [$debut, $fin] = $vue === 'jour'
            ? [$ancre->copy()->startOfDay(), $ancre->copy()->endOfDay()]
            : [$ancre->copy()->startOfWeek(), $ancre->copy()->endOfWeek()];

        $vehicules = Vehicule::where('statut', '!=', 'hors_service')
            ->with('agence')
            ->orderBy('immatriculation')
            ->get();

        // Tout ce qui occupe reellement un vehicule sur la periode affichee.
        $reservations = Reservation::query()
            ->with(['demandeur', 'vehicule', 'chauffeur'])
            ->whereIn('statut', ['en_attente', 'validee', 'en_cours', 'terminee'])
            ->whereNotNull('vehicule_id')
            ->where('date_debut', '<', $fin)
            ->where('date_fin', '>', $debut)
            ->orderBy('date_debut')
            ->get();

        $jours = collect();
        for ($j = $debut->copy(); $j->lte($fin); $j->addDay()) {
            $jours->push($j->copy());
        }

        return view('planning.index', [
            'vue' => $vue,
            'ancre' => $ancre,
            'debut' => $debut,
            'fin' => $fin,
            'jours' => $jours,
            'vehicules' => $vehicules,
            'reservations' => $reservations,
            'parVehicule' => $reservations->groupBy('vehicule_id'),
            'libresMaintenant' => $this->libresMaintenant($vehicules),
            'heureDebut' => self::HEURE_DEBUT,
            'heureFin' => self::HEURE_FIN,
        ]);
    }

    /**
     * Verification de disponibilite sur un creneau precis.
     * Appelee en direct depuis le planning, sans rechargement de page.
     */
    public function disponibilite(Request $request)
    {
        $donnees = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ]);

        $debut = Carbon::parse($donnees['date_debut']);
        $fin = Carbon::parse($donnees['date_fin']);

        $libres = Vehicule::libresSur($debut, $fin)->orderBy('immatriculation')->get();

        $occupes = Reservation::with(['demandeur', 'vehicule'])
            ->whereIn('statut', ['en_attente', 'validee', 'en_cours'])
            ->whereNotNull('vehicule_id')
            ->periode($debut, $fin)
            ->get();

        return response()->json([
            'creneau' => [
                'debut' => $debut->format('d/m/Y H:i'),
                'fin' => $fin->format('d/m/Y H:i'),
            ],
            'nombre_libres' => $libres->count(),
            'libres' => $libres->map(fn ($v) => [
                'id' => $v->id,
                'immatriculation' => $v->immatriculation,
                'libelle' => $v->libelle,
                'places' => $v->nombre_places,
            ])->values(),
            'occupes' => $occupes->map(fn ($r) => [
                'immatriculation' => $r->vehicule?->immatriculation,
                'demandeur' => $r->demandeur->nom_complet,
                'departement' => $r->departement_libelle,
                'creneau' => $r->date_debut->format('d/m H:i').' → '.$r->date_fin->format('d/m H:i'),
                'statut' => $r->statut_libelle,
            ])->values(),
        ]);
    }

    /** Etat instantane de la flotte, affiche en tete du planning. */
    protected function libresMaintenant($vehicules)
    {
        $occupes = Reservation::whereIn('statut', ['validee', 'en_cours'])
            ->whereNotNull('vehicule_id')
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->pluck('vehicule_id')
            ->unique();

        return $vehicules->filter(
            fn ($v) => $v->statut === 'disponible' && ! $occupes->contains($v->id)
        );
    }

    protected function ancre(Request $request): Carbon
    {
        try {
            return $request->filled('jour') ? Carbon::parse($request->input('jour')) : today();
        } catch (\Throwable) {
            return today();
        }
    }
}
