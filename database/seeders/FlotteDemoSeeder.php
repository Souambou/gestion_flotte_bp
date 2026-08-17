<?php

namespace Database\Seeders;

use App\Models\Agence;
use App\Models\Chauffeur;
use App\Models\Checklist;
use App\Models\Deplacement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicule;
use App\Support\Departements;
use Illuminate\Database\Seeder;

/**
 * Jeu de donnees de demonstration : flotte, chauffeurs et historique de reservations.
 * A ne pas executer en production (php artisan db:seed --class=RolePermissionSeeder suffit).
 */
class FlotteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $agences = Agence::all();
        $responsable = User::whereHas('roles', fn ($q) => $q->where('name', 'responsable_flotte'))->first();
        $commerciaux = User::whereHas('roles', fn ($q) => $q->where('name', 'commercial'))->get();

        $modeles = [
            ['Toyota', 'Hilux', 'pickup', 5, 'gasoil'],
            ['Toyota', 'Land Cruiser', 'suv', 7, 'gasoil'],
            ['Toyota', 'Corolla', 'berline', 5, 'essence'],
            ['Nissan', 'Navara', 'pickup', 5, 'gasoil'],
            ['Mitsubishi', 'Pajero', 'suv', 7, 'gasoil'],
            ['Renault', 'Master', 'utilitaire', 3, 'gasoil'],
            ['Mercedes', 'Actros', 'camion_citerne', 3, 'gasoil'],
            ['Hyundai', 'H1', 'minibus', 12, 'gasoil'],
            ['Peugeot', '301', 'berline', 5, 'essence'],
            ['Ford', 'Ranger', 'pickup', 5, 'gasoil'],
        ];

        foreach ($modeles as $index => [$marque, $modele, $type, $places, $carburant]) {
            Vehicule::firstOrCreate(
                ['immatriculation' => sprintf('%s-%04d-RB', ['AA', 'AB', 'AC'][$index % 3], 1000 + $index * 37)],
                [
                    'marque' => $marque,
                    'modele' => $modele,
                    'type' => $type,
                    'annee' => 2018 + ($index % 6),
                    'carburant' => $carburant,
                    'nombre_places' => $places,
                    'kilometrage' => random_int(15000, 180000),
                    'statut' => $index === 6 ? 'en_maintenance' : 'disponible',
                    'agence_id' => $agences->random()->id,
                    'date_mise_en_service' => now()->subYears(random_int(1, 6)),
                    'date_expiration_assurance' => now()->addDays(random_int(-10, 300)),
                    'date_visite_technique' => now()->addDays(random_int(-5, 250)),
                    'date_prochaine_maintenance' => now()->addDays(random_int(5, 120)),
                    'km_prochaine_maintenance' => random_int(20000, 200000),
                ]
            );
        }

        $noms = [
            ['Agbodjan', 'Pierre'], ['Kponou', 'Rachid'], ['Sossou', 'Émile'],
            ['Tossou', 'Bernard'], ['Zinsou', 'Camille'], ['Gbaguidi', 'Léon'],
        ];

        foreach ($noms as $index => [$nom, $prenom]) {
            Chauffeur::firstOrCreate(
                ['numero_permis' => 'BJ'.(700000 + $index)],
                [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'telephone' => '+2299'.random_int(1000000, 9999999),
                    'categorie_permis' => $index < 2 ? 'C' : 'B',
                    'date_expiration_permis' => now()->addDays(random_int(-20, 700)),
                    'date_embauche' => now()->subYears(random_int(1, 10)),
                    'statut' => 'disponible',
                ]
            );
        }

        if (! $responsable || $commerciaux->isEmpty()) {
            return;
        }

        $vehicules = Vehicule::where('statut', 'disponible')->get();
        $chauffeurs = Chauffeur::all();

        $trajets = [
            ['Siège Cotonou', 'Dépôt Parakou'],
            ['Cotonou', 'Porto-Novo'],
            ['Cotonou', 'Bohicon'],
            ['Parakou', 'Natitingou'],
            ['Cotonou', 'Lomé'],
            ['Abomey-Calavi', 'Cotonou'],
        ];

        $departements = Departements::cles();

        // Historique : 24 reservations reparties sur les 60 derniers jours.
        for ($i = 0; $i < 24; $i++) {
            $debut = now()->subDays(random_int(0, 60))->setTime(random_int(6, 15), [0, 30][random_int(0, 1)]);
            $fin = (clone $debut)->addHours(random_int(4, 30));
            [$depart, $arrivee] = $trajets[array_rand($trajets)];
            $vehicule = $vehicules->random();
            $avecChauffeur = (bool) random_int(0, 1);

            $statut = match (true) {
                $debut->isFuture() => ['en_attente', 'validee'][random_int(0, 1)],
                $fin->isPast() => ['terminee', 'terminee', 'refusee', 'annulee'][random_int(0, 3)],
                default => 'en_cours',
            };

            $reservation = Reservation::create([
                'user_id' => $commerciaux->random()->id,
                'vehicule_id' => in_array($statut, ['en_attente', 'refusee']) ? null : $vehicule->id,
                'chauffeur_id' => ($avecChauffeur && ! in_array($statut, ['en_attente', 'refusee'])) ? $chauffeurs->random()->id : null,
                'avec_chauffeur' => $avecChauffeur,
                'departement' => $departements[array_rand($departements)],
                'type_deplacement' => ['sortie_simple', 'mission'][random_int(0, 1)],
                'date_debut' => $debut,
                'date_fin' => $fin,
                'lieu_depart' => $depart,
                'lieu_arrivee' => $arrivee,
                'motif' => 'Livraison et suivi client sur l\'axe '.$depart.' — '.$arrivee.'.',
                'statut' => $statut,
                'motif_refus' => $statut === 'refusee' ? 'Aucun véhicule du type demandé n\'était disponible sur ce créneau.' : null,
                'alternative_proposee' => $statut === 'refusee' ? 'Créneau du lendemain matin avec un pick-up disponible.' : null,
                'traite_par' => in_array($statut, ['validee', 'refusee', 'en_cours', 'terminee']) ? $responsable->id : null,
                'traite_at' => in_array($statut, ['validee', 'refusee', 'en_cours', 'terminee']) ? (clone $debut)->subHours(random_int(2, 20)) : null,
                'created_at' => (clone $debut)->subDays(random_int(1, 5)),
            ]);

            if (in_array($statut, ['en_cours', 'terminee'])) {
                $kmDepart = $vehicule->kilometrage - random_int(500, 5000);
                $kmArrivee = $kmDepart + random_int(40, 620);

                $deplacement = Deplacement::create([
                    'reservation_id' => $reservation->id,
                    'vehicule_id' => $vehicule->id,
                    'chauffeur_id' => $reservation->chauffeur_id,
                    'depart_reel_at' => $debut,
                    'arrivee_reelle_at' => $statut === 'terminee' ? $fin : null,
                    'km_depart' => max(0, $kmDepart),
                    'km_arrivee' => $statut === 'terminee' ? $kmArrivee : null,
                    'carburant_consomme' => $statut === 'terminee' ? random_int(20, 90) : null,
                    'cout_carburant' => $statut === 'terminee' ? random_int(15000, 90000) : null,
                    'autres_frais' => $statut === 'terminee' ? random_int(0, 25000) : null,
                    'statut' => $statut === 'terminee' ? 'terminee' : 'en_cours',
                    'ouverte_par' => $responsable->id,
                    'cloturee_par' => $statut === 'terminee' ? $responsable->id : null,
                ]);
            }
        }

        $this->genererControlesMatinaux($responsable);
    }

    /**
     * Controle matinal sur les sept derniers jours : un releve par vehicule
     * et par jour ouvre, comme en exploitation reelle.
     */
    protected function genererControlesMatinaux(User $auteur): void
    {
        $vehicules = Vehicule::where('statut', '!=', 'hors_service')->get();

        for ($j = 6; $j >= 0; $j--) {
            $jour = now()->subDays($j)->startOfDay();

            if ($jour->isWeekend()) {
                continue;
            }

            foreach ($vehicules as $vehicule) {
                // Tous les vehicules ne sont pas controles chaque jour.
                if ($j > 0 && random_int(1, 10) === 1) {
                    continue;
                }

                $points = [];
                foreach (config('beninpetro.checklist') as $rubrique) {
                    foreach ($rubrique as $cle => $libelle) {
                        $points[$cle] = [
                            'statut' => random_int(1, 14) === 1 ? 'a_surveiller' : 'conforme',
                            'commentaire' => null,
                        ];
                    }
                }

                Checklist::firstOrCreate(
                    ['vehicule_id' => $vehicule->id, 'date_controle' => $jour->toDateString()],
                    [
                        'user_id' => $auteur->id,
                        'kilometrage' => max(0, $vehicule->kilometrage - random_int(0, 400) * $j),
                        'niveau_carburant' => random_int(25, 100),
                        'etat_general' => random_int(1, 12) === 1 ? 'moyen' : 'bon',
                        'points' => $points,
                        'completee_at' => $jour->copy()->setTime(7, random_int(0, 45)),
                    ]
                );
            }
        }
    }
}