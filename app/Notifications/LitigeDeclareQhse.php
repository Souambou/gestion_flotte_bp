<?php

namespace App\Notifications;

use App\Models\Litige;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Information systematique du service QHSE a chaque litige declare.
 *
 * Le QHSE doit avoir connaissance de tout incident survenu sur la flotte,
 * qu'il en soit ou non le declarant : l'envoi est donc automatique et ne
 * depend d'aucune action du responsable de flotte.
 */
class LitigeDeclareQhse extends Notification
{
    use Queueable;

    public function __construct(public Litige $litige)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $litige = $this->litige;
        $reservation = $litige->reservation;

        $message = (new MailMessage)
            ->subject("[QHSE] Litige {$litige->reference} — {$litige->objet}")
            ->greeting('Signalement automatique')
            ->line("Un litige vient d'être déclaré sur la flotte et vous est transmis pour information.")
            ->line('')
            ->line("**Référence :** {$litige->reference}")
            ->line("**Nature :** {$litige->type_libelle}")
            ->line('**Gravité :** '.ucfirst($litige->gravite))
            ->line("**Objet :** {$litige->objet}")
            ->line('**Déclaré par :** '.($litige->declarant?->nom_complet ?? 'Non renseigné'))
            ->line('**Date de déclaration :** '.$litige->created_at->format('d/m/Y à H:i'));

        if ($litige->vehicule) {
            $message->line("**Véhicule concerné :** {$litige->vehicule->libelle}");
        }

        if ($reservation) {
            $message->line("**Réservation liée :** {$reservation->code} — {$reservation->trajet}")
                ->line('**Département demandeur :** '.$reservation->departement_libelle);
        }

        return $message
            ->line('')
            ->line('**Description :**')
            ->line($litige->description)
            ->action('Consulter le dossier', route('litiges.show', $litige))
            ->salutation('Plateforme de gestion de flotte — Bénin Pétro SA');
    }
}
