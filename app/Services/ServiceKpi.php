<?php

namespace App\Services;

use App\Models\Chauffeur;
use App\Models\Checklist;
use App\Models\Deplacement;
use App\Models\Litige;
use App\Models\Reservation;
use App\Models\Vehicule;
use App\Support\Departements;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcule les indicateurs de performance affiches dans les tableaux de bord
 * et dans la section Rapports (generee automatiquement par le serveur).
 */
class ServiceKpi
{
    public function __construct(
        protected Carbon $debut,
        protected Carbon $fin,
    ) {}

    public static function periode($debut = null, $fin = null): self
    {
        return new self(
            $debut ? Carbon::parse($debut)->startOfDay() : now()->startOfMonth(),
            $fin ? Carbon::parse($fin)->endOfDay() : now()->endOfMonth(),
        );
    }

    public function debut(): Carbon
    {
        return $this->debut;
    }

    public function fin(): Carbon
    {
        return $this->fin;
    }

    /** Synthese generale utilisee par les tableaux de bord. */
    public function synthese(): array
    {
        return [
            'reservations_total' => $this->reservationsPeriode()->count(),
            'reservations_en_attente' => Reservation::enAttente()->count(),
            'reservations_validees' => $this->reservationsPeriode()->where('statut', 'validee')->count(),
            'reservations_terminees' => $this->reservationsPeriode()->where('statut', 'terminee')->count(),
            'reservations_refusees' => $this->reservationsPeriode()->where('statut', 'refusee')->count(),
            'reservations_annulees' => $this->reservationsPeriode()->where('statut', 'annulee')->count(),
            'taux_validation' => $this->tauxValidation(),
            'delai_moyen_validation' => $this->delaiMoyenValidationHeures(),
            'taux_occupation_flotte' => $this->tauxOccupationFlotte(),
            'vehicules_total' => Vehicule::count(),
            'vehicules_disponibles' => Vehicule::where('statut', 'disponible')->count(),
            'vehicules_en_deplacement' => Vehicule::where('statut', 'en_deplacement')->count(),
            'vehicules_en_maintenance' => Vehicule::where('statut', 'en_maintenance')->count(),
            'chauffeurs_total' => Chauffeur::count(),
            'chauffeurs_disponibles' => Chauffeur::where('statut', 'disponible')->count(),
            'deplacements_en_cours' => Deplacement::where('statut', 'en_cours')->count(),
            'km_parcourus' => $this->kilometresParcourus(),
            'cout_total' => $this->coutTotal(),
            'litiges_ouverts' => Litige::whereIn('statut', ['ouvert', 'en_traitement'])->count(),
            'anomalies_checklists' => $this->anomaliesChecklists(),
        ];
    }

    public function reservationsPeriode()
    {
        return Reservation::whereBetween('date_debut', [$this->debut, $this->fin]);
    }

    /** Taux d'occupation moyen de la flotte sur la periode (%). */
    public function tauxOccupationFlotte(): float
    {
        $vehicules = Vehicule::whereNotIn('statut', ['hors_service'])->get();

        if ($vehicules->isEmpty()) {
            return 0;
        }

        $somme = $vehicules->sum(fn (Vehicule $v) => $v->tauxOccupation($this->debut, $this->fin));

        return round($somme / $vehicules->count(), 1);
    }

    /** Taux de validation des demandes traitees (%). */
    public function tauxValidation(): float
    {
        $traitees = $this->reservationsPeriode()->whereIn('statut', ['validee', 'refusee', 'en_cours', 'terminee'])->count();

        if ($traitees === 0) {
            return 0;
        }

        $validees = $this->reservationsPeriode()->whereIn('statut', ['validee', 'en_cours', 'terminee'])->count();

        return round($validees / $traitees * 100, 1);
    }

    /**
     * Demandes par service, avec taux de satisfaction.
     *
     * Reprend la structure du suivi mensuel tenu par le service : nombre de
     * demandes, satisfaites, non satisfaites, et les deux taux correspondants.
     * Une demande est satisfaite des lors qu'elle a ete honoree (validee,
     * en cours ou terminee) ; refus et annulations sont non satisfaits.
     */
    public function demandesParDepartement(): Collection
    {
        $comptes = Reservation::query()
            ->whereBetween('created_at', [$this->debut, $this->fin])
            ->selectRaw('departement, statut, COUNT(*) as total')
            ->groupBy('departement', 'statut')
            ->get()
            ->groupBy('departement');

        $satisfaits = ['validee', 'en_cours', 'terminee'];

        return collect(Departements::tous())
            ->map(function ($departement, $cle) use ($comptes, $satisfaits) {
                $lignes = $comptes->get($cle, collect());

                $total = (int) $lignes->sum('total');
                $satisfait = (int) $lignes->whereIn('statut', $satisfaits)->sum('total');
                $nonSatisfait = $total - $satisfait;

                return [
                    'cle' => $cle,
                    'sigle' => $departement['sigle'],
                    'libelle' => $departement['libelle'],
                    'demandes' => $total,
                    'satisfaites' => $satisfait,
                    'non_satisfaites' => $nonSatisfait,
                    'taux_satisfaction' => $total ? round($satisfait / $total * 100, 1) : 0.0,
                    'taux_non_satisfaction' => $total ? round($nonSatisfait / $total * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->sortByDesc('demandes')
            ->values();
    }

    /** Ligne de total, calculee sur l'ensemble des services. */
    public function totalDemandesParDepartement(Collection $lignes): array
    {
        $demandes = (int) $lignes->sum('demandes');
        $satisfaites = (int) $lignes->sum('satisfaites');
        $nonSatisfaites = $demandes - $satisfaites;

        return [
            'demandes' => $demandes,
            'satisfaites' => $satisfaites,
            'non_satisfaites' => $nonSatisfaites,
            'taux_satisfaction' => $demandes ? round($satisfaites / $demandes * 100, 1) : 0.0,
            'taux_non_satisfaction' => $demandes ? round($nonSatisfaites / $demandes * 100, 1) : 0.0,
        ];
    }

    /**
     * Justificatif de consommation d'un vehicule, deplacement par deplacement.
     *
     * Chaque ligne porte le releve terrain : kilometrage de depart et
     * d'arrivee, distance parcourue, consommation ramenee aux 100 km, et
     * le cout associe. Le solde cumule suit la logique du suivi papier
     * actuellement tenu par le service.
     */
    public function consommationVehicule(Vehicule $vehicule): Collection
    {
        $deplacements = Deplacement::with('reservation')
            ->where('vehicule_id', $vehicule->id)
            ->whereBetween('depart_reel_at', [$this->debut, $this->fin])
            ->whereNotNull('km_depart')
            ->orderBy('depart_reel_at')
            ->get();

        $solde = 0.0;
        $index = 0;

        return $deplacements->map(function (Deplacement $d) use (&$solde, &$index) {
            $index++;
            $distance = $d->distance_parcourue;
            $consomme = (float) $d->carburant_consomme;

            // Le carburant consomme est un debit ; le solde cumule le suit.
            $solde -= $consomme;

            return [
                'index' => $index,
                'date' => $d->depart_reel_at,
                'motif' => $d->reservation?->motif,
                'trajet' => $d->reservation?->trajet,
                'km_debut' => $d->km_depart,
                'km_fin' => $d->km_arrivee,
                'km_parcouru' => $distance,
                'consommation_100km' => ($distance && $consomme) ? round($consomme / $distance * 100, 2) : null,
                'debit_litres' => $consomme ?: null,
                'solde_litres' => round($solde, 2),
                'cout' => (float) $d->cout_carburant,
                'cout_total' => $d->cout_total,
            ];
        });
    }

    /** Delai moyen entre la soumission et la decision, en heures. */
    public function delaiMoyenValidationHeures(): float
    {
        $reservations = $this->reservationsPeriode()->whereNotNull('traite_at')->get(['created_at', 'traite_at']);

        if ($reservations->isEmpty()) {
            return 0;
        }

        return round($reservations->avg(fn ($r) => $r->created_at->diffInMinutes($r->traite_at)) / 60, 1);
    }

    public function kilometresParcourus(): int
    {
        return (int) Deplacement::whereBetween('depart_reel_at', [$this->debut, $this->fin])
            ->whereNotNull('km_arrivee')
            ->selectRaw('COALESCE(SUM(km_arrivee - km_depart), 0) as total')
            ->value('total');
    }

    public function coutTotal(): float
    {
        return (float) Deplacement::whereBetween('depart_reel_at', [$this->debut, $this->fin])
            ->selectRaw('COALESCE(SUM(COALESCE(cout_carburant,0) + COALESCE(autres_frais,0)), 0) as total')
            ->value('total');
    }

    public function anomaliesChecklists(): int
    {
        return Checklist::whereBetween('created_at', [$this->debut, $this->fin])
            ->whereNotNull('anomalies')
            ->where('anomalies', '!=', '')
            ->count();
    }

    /** Evolution jour par jour du nombre de reservations. */
    public function evolutionReservations(): Collection
    {
        $lignes = Reservation::whereBetween('date_debut', [$this->debut, $this->fin])
            ->selectRaw('DATE(date_debut) as jour, COUNT(*) as total')
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $serie = collect();
        for ($date = $this->debut->copy(); $date->lte($this->fin); $date->addDay()) {
            $cle = $date->format('Y-m-d');
            $serie->put($cle, (int) ($lignes[$cle] ?? 0));
        }

        return $serie;
    }

    public function repartitionStatuts(): Collection
    {
        return Reservation::whereBetween('date_debut', [$this->debut, $this->fin])
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');
    }

    /** Classement des vehicules par utilisation. */
    public function performanceVehicules(): Collection
    {
        return Vehicule::with('agence')->get()->map(function (Vehicule $vehicule) {
            $deplacements = $vehicule->deplacements()
                ->whereBetween('depart_reel_at', [$this->debut, $this->fin])
                ->get();

            return [
                'vehicule' => $vehicule,
                'reservations' => $vehicule->reservations()
                    ->whereBetween('date_debut', [$this->debut, $this->fin])
                    ->whereIn('statut', ['validee', 'en_cours', 'terminee'])
                    ->count(),
                'deplacements' => $deplacements->count(),
                'kilometres' => $deplacements->sum(fn ($m) => $m->distance_parcourue ?? 0),
                'cout' => $deplacements->sum(fn ($m) => $m->cout_total),
                'taux_occupation' => $vehicule->tauxOccupation($this->debut, $this->fin),
            ];
        })->sortByDesc('taux_occupation')->values();
    }

    /** Classement des chauffeurs par activite. */
    public function performanceChauffeurs(): Collection
    {
        return Chauffeur::all()->map(function (Chauffeur $chauffeur) {
            $deplacements = $chauffeur->deplacements()
                ->whereBetween('depart_reel_at', [$this->debut, $this->fin])
                ->get();

            return [
                'chauffeur' => $chauffeur,
                'deplacements' => $deplacements->count(),
                'kilometres' => $deplacements->sum(fn ($m) => $m->distance_parcourue ?? 0),
                'heures' => round($deplacements->sum(function ($m) {
                    return $m->depart_reel_at && $m->arrivee_reelle_at
                        ? $m->depart_reel_at->diffInMinutes($m->arrivee_reelle_at)
                        : 0;
                }) / 60, 1),
            ];
        })->sortByDesc('deplacements')->values();
    }
}
