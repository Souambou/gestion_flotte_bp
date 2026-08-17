<?php

namespace App\Services;

use App\Models\Chauffeur;
use App\Models\Deplacement;
use App\Models\Reservation;
use App\Models\Vehicule;
use App\Notifications\ReservationRefusee;
use App\Notifications\ReservationSoumise;
use App\Notifications\ReservationValidee;
use App\Models\JournalActivite;
use App\Models\User;
use App\Support\Notificateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceReservation
{
    public function __construct(protected ServiceGoogleMaps $maps) {}

    /** Cree une demande de reservation et notifie les responsables de flotte. */
    public function creer(array $donnees, User $demandeur): Reservation
    {
        return DB::transaction(function () use ($donnees, $demandeur) {
            $estimation = $this->maps->distance($donnees['lieu_depart'], $donnees['lieu_arrivee']);

            $reservation = Reservation::create(array_merge($donnees, [
                'user_id' => $demandeur->id,
                'statut' => 'en_attente',
                'distance_estimee_km' => $estimation['distance_km'] ?? null,
                'duree_estimee_min' => $estimation['duree_min'] ?? null,
            ]));

            JournalActivite::enregistrer('reservation.creee', $reservation, "Demande {$reservation->code} soumise");

            $this->notifierResponsables($reservation);

            return $reservation;
        });
    }

    /** Valide une demande, affecte le vehicule/chauffeur et prepare le déplacement. */
    public function valider(Reservation $reservation, array $donnees, User $validateur): Reservation
    {
        if (! $reservation->peutEtreValidee()) {
            throw ValidationException::withMessages([
                'statut' => 'Cette demande a déjà été traitée.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $donnees, $validateur) {
            $vehicule = Vehicule::findOrFail($donnees['vehicule_id']);

            if (! $vehicule->estLibreSur($reservation->date_debut, $reservation->date_fin, $reservation->id)) {
                throw ValidationException::withMessages([
                    'vehicule_id' => 'Ce véhicule est déjà engagé sur ce créneau. Choisissez une alternative disponible.',
                ]);
            }

            $chauffeur = null;
            if ($reservation->avec_chauffeur) {
                if (empty($donnees['chauffeur_id'])) {
                    throw ValidationException::withMessages([
                        'chauffeur_id' => 'La demande porte sur un véhicule avec chauffeur : sélectionnez un chauffeur.',
                    ]);
                }

                $chauffeur = Chauffeur::findOrFail($donnees['chauffeur_id']);

                if (! $chauffeur->estLibreSur($reservation->date_debut, $reservation->date_fin, $reservation->id)) {
                    throw ValidationException::withMessages([
                        'chauffeur_id' => 'Ce chauffeur est déjà affecté sur ce créneau.',
                    ]);
                }
            }

            $reservation->update([
                'vehicule_id' => $vehicule->id,
                'chauffeur_id' => $chauffeur?->id,
                'statut' => 'validee',
                'traite_par' => $validateur->id,
                'traite_at' => now(),
                'motif_refus' => null,
            ]);

            // Le deplacement est cree des la validation : il porte le suivi terrain.
            Deplacement::firstOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'vehicule_id' => $vehicule->id,
                    'chauffeur_id' => $chauffeur?->id,
                    'statut' => 'planifiee',
                    'ouverte_par' => $validateur->id,
                ]
            );

            JournalActivite::enregistrer('reservation.validee', $reservation, "Demande {$reservation->code} validée");

            $this->notifier($reservation->demandeur, new ReservationValidee($reservation));

            return $reservation->fresh(['vehicule', 'chauffeur', 'deplacement']);
        });
    }

    /** Refuse une demande en consignant le motif et l'alternative proposee. */
    public function refuser(Reservation $reservation, array $donnees, User $validateur): Reservation
    {
        if (! $reservation->peutEtreValidee()) {
            throw ValidationException::withMessages(['statut' => 'Cette demande a déjà été traitée.']);
        }

        $reservation->update([
            'statut' => 'refusee',
            'motif_refus' => $donnees['motif_refus'],
            'alternative_proposee' => $donnees['alternative_proposee'] ?? null,
            'traite_par' => $validateur->id,
            'traite_at' => now(),
        ]);

        JournalActivite::enregistrer('reservation.refusee', $reservation, "Demande {$reservation->code} refusée");

        $this->notifier($reservation->demandeur, new ReservationRefusee($reservation));

        return $reservation;
    }

    /** Annule une reservation (demandeur, responsable ou administrateur). */
    public function annuler(Reservation $reservation, ?string $motif, User $auteur): Reservation
    {
        if (! $reservation->peutEtreAnnulee()) {
            throw ValidationException::withMessages([
                'statut' => 'Une réservation '.strtolower($reservation->statut_libelle).' ne peut plus être annulée.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $motif, $auteur) {
            $reservation->update([
                'statut' => 'annulee',
                'motif_annulation' => $motif,
                'annule_par' => $auteur->id,
                'annule_at' => now(),
            ]);

            $reservation->deplacement?->delete();

            if ($reservation->vehicule && $reservation->vehicule->statut === 'en_deplacement') {
                $reservation->vehicule->update(['statut' => 'disponible']);
            }

            JournalActivite::enregistrer('reservation.annulee', $reservation, "Demande {$reservation->code} annulée");

            // On previent l'autre partie : le demandeur ou les responsables.
            if ($auteur->id !== $reservation->user_id) {
                $this->notifier($reservation->demandeur, new ReservationRefusee($reservation, annulation: true));
            } else {
                $this->notifierResponsables($reservation, annulation: true);
            }

            return $reservation;
        });
    }

    /** Propose les vehicules et chauffeurs reellement libres sur le creneau. */
    public function alternatives(Reservation $reservation): array
    {
        $libres = Vehicule::libresSur($reservation->date_debut, $reservation->date_fin, $reservation->id)
            ->orderBy('marque')
            ->get();

        return [
            // Le demandeur ne choisit plus de type de vehicule : le responsable
            // arbitre sur l'ensemble des vehicules libres du creneau.
            'vehicules' => $libres,
            'tous_vehicules' => $libres,
            'chauffeurs' => Chauffeur::libresSur($reservation->date_debut, $reservation->date_fin, $reservation->id)
                ->orderBy('nom')
                ->get(),
        ];
    }

    protected function notifierResponsables(Reservation $reservation, bool $annulation = false): void
    {
        $destinataires = User::actifs()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['responsable_flotte', 'administrateur']))
            ->get();

        foreach ($destinataires as $destinataire) {
            $this->notifier($destinataire, new ReservationSoumise($reservation, $annulation));
        }
    }

    /**
     * Envoie une notification sans jamais faire echouer l'operation metier.
     *
     * Une reservation validee doit le rester meme si le serveur de messagerie
     * est injoignable : l'incident est journalise, l'utilisateur n'est pas bloque.
     */
    protected function notifier(User $destinataire, $notification): void
    {
        Notificateur::envoyer($destinataire, $notification);
    }
}
