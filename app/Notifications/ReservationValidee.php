<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Services\ServiceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationValidee extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->reservation;

        $message = (new MailMessage)
            ->subject("Réservation confirmée — {$r->code}")
            ->greeting('Bonjour '.$notifiable->prenom.',')
            ->line('Votre demande de véhicule est confirmée.')
            ->line('Véhicule : '.($r->vehicule?->libelle ?? 'à confirmer'))
            ->line('Départ : '.$r->date_debut->format('d/m/Y à H:i').' — '.$r->lieu_depart)
            ->line('Retour prévu : '.$r->date_fin->format('d/m/Y à H:i'));

        if ($r->chauffeur) {
            $message->line('Chauffeur : '.$r->chauffeur?->nom_complet.' ('.$r->chauffeur?->telephone.')');
        }

        return $message
            ->action('Voir ma réservation', route('reservations.show', $r))
            ->salutation('Bénin Pétro — Gestion de flotte');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre' => 'Réservation confirmée',
            'message' => $this->reservation->code.' — '.($this->reservation->vehicule?->libelle ?? ''),
            'lien' => route('reservations.show', $this->reservation),
            'icone' => 'validation',
        ];
    }

    public function toSms(object $notifiable): void
    {
        if ($notifiable->telephone) {
            app(ServiceNotification::class)->envoyerSms(
                $notifiable->telephone,
                "Bénin Pétro : réservation {$this->reservation->code} confirmée pour le ".$this->reservation->date_debut->format('d/m à H:i').'.'
            );
        }
    }
}
