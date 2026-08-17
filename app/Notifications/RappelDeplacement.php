<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RappelDéplacement extends Notification
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

        return (new MailMessage)
            ->subject("Rappel — départ le {$r->date_debut->format('d/m/Y à H:i')}")
            ->greeting('Bonjour '.$notifiable->prenom.',')
            ->line("Rappel de votre déplacement {$r->code}.")
            ->line("Véhicule : {$r->vehicule?->libelle}")
            ->line("Trajet : {$r->trajet}")
            ->line('La checklist avant déplacement doit être remplie avant le départ.')
            ->action('Ouvrir le déplacement', route('reservations.show', $r));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre' => 'Départ imminent',
            'message' => $this->reservation->code.' — départ le '.$this->reservation->date_debut->format('d/m à H:i'),
            'lien' => route('reservations.show', $this->reservation),
            'icone' => 'rappel',
        ];
    }
}
