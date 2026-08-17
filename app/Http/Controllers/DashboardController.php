<?php

namespace App\Http\Controllers;

use App\Models\Chauffeur;
use App\Models\Litige;
use App\Models\Maintenance;
use App\Models\Deplacement;
use App\Models\Reservation;
use App\Models\Vehicule;
use App\Services\ServiceKpi;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $utilisateur = $request->user();
        $kpi = ServiceKpi::periode($request->input('debut'), $request->input('fin'));

        if ($utilisateur->estCommercial() && ! $utilisateur->estAdministrateur() && ! $utilisateur->estResponsableFlotte()) {
            return $this->tableauCommercial($request, $kpi);
        }

        return $this->tableauFlotte($request, $kpi);
    }

    protected function tableauCommercial(Request $request, ServiceKpi $kpi)
    {
        $utilisateur = $request->user();

        $mesReservations = Reservation::where('user_id', $utilisateur->id);

        return view('dashboard.commercial', [
            'kpi' => $kpi,
            'statistiques' => [
                'en_attente' => (clone $mesReservations)->where('statut', 'en_attente')->count(),
                'validees' => (clone $mesReservations)->where('statut', 'validee')->count(),
                'en_cours' => (clone $mesReservations)->where('statut', 'en_cours')->count(),
                'terminees' => (clone $mesReservations)->where('statut', 'terminee')->count(),
            ],
            'prochaines' => (clone $mesReservations)
                ->whereIn('statut', ['validee', 'en_cours'])
                ->where('date_fin', '>=', now())
                ->with(['vehicule', 'chauffeur'])
                ->orderBy('date_debut')
                ->take(5)
                ->get(),
            'recentes' => (clone $mesReservations)
                ->with(['vehicule'])
                ->latest()
                ->take(8)
                ->get(),
            'vehiculesDisponibles' => Vehicule::disponibles()->count(),
        ]);
    }

    protected function tableauFlotte(Request $request, ServiceKpi $kpi)
    {
        $synthese = $kpi->synthese();

        $alertes = collect();

        foreach (Vehicule::whereNotIn('statut', ['hors_service'])->get() as $vehicule) {
            foreach ($vehicule->alertes() as $alerte) {
                $alertes->push(array_merge($alerte, [
                    'vehicule' => $vehicule,
                    'lien' => route('vehicules.show', $vehicule),
                ]));
            }
        }

        $seuilPermis = (int) config('beninpetro.maintenance.alerte_permis_jours');
        foreach (Chauffeur::whereNotNull('date_expiration_permis')->get() as $chauffeur) {
            if ($chauffeur->date_expiration_permis->lte(now()->addDays($seuilPermis))) {
                $alertes->push([
                    'niveau' => $chauffeur->permis_expire ? 'danger' : 'attention',
                    'message' => 'Permis de '.$chauffeur->nom_complet.' — expiration le '.$chauffeur->date_expiration_permis->format('d/m/Y'),
                    'vehicule' => null,
                    'lien' => route('chauffeurs.show', $chauffeur),
                ]);
            }
        }

        return view('dashboard.flotte', [
            'kpi' => $kpi,
            'synthese' => $synthese,
            'evolution' => $kpi->evolutionReservations(),
            'repartition' => $kpi->repartitionStatuts(),
            'demandesEnAttente' => Reservation::enAttente()
                ->with(['demandeur', 'vehicule'])
                ->orderBy('date_debut')
                ->take(6)
                ->get(),
            'déplacementsEnCours' => Deplacement::where('statut', 'en_cours')
                ->with(['reservation.demandeur', 'vehicule', 'chauffeur'])
                ->orderBy('depart_reel_at')
                ->take(6)
                ->get(),
            'departsDuJour' => Reservation::whereIn('statut', ['validee', 'en_cours'])
                ->whereDate('date_debut', today())
                ->with(['vehicule', 'chauffeur', 'demandeur'])
                ->orderBy('date_debut')
                ->get(),
            'alertes' => $alertes->sortBy(fn ($a) => $a['niveau'] === 'danger' ? 0 : 1)->take(8)->values(),
            'maintenancesAVenir' => Maintenance::whereIn('statut', ['planifiee', 'en_cours'])
                ->with('vehicule')
                ->orderBy('date_prevue')
                ->take(5)
                ->get(),
            'litigesOuverts' => Litige::whereIn('statut', ['ouvert', 'en_traitement'])
                ->with('reservation')
                ->latest()
                ->take(5)
                ->get(),
            'topVehicules' => $kpi->performanceVehicules()->take(5),
        ]);
    }
}
