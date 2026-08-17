<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Services\ServiceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationRefusee extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation, public bool $annulation = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->reservation;

        if ($this->annulation) {
            return (new MailMessage)
                ->subject("Réservation annulée — {$r->code}")
                ->greeting('Bonjour '.$notifiable->prenom.',')
                ->line("Votre réservation {$r->code} a été annulée.")
                ->when($r->motif_annulation, fn ($m) => $m->line("Motif : {$r->motif_annulation}"))
                ->action('Soumettre une nouvelle demande', route('reservations.create'))
                ->salutation('Bénin Pétro — Gestion de flotte');
        }

        $message = (new MailMessage)
            ->subject("Demande non retenue — {$r->code}")
            ->greeting('Bonjour '.$notifiable->prenom.',')
            ->line("Votre demande {$r->code} n'a pas pu être validée.")
            ->line("Motif : {$r->motif_refus}");

        if ($r->alternative_proposee) {
            $message->line("Alternative proposée : {$r->alternative_proposee}");
        }

        return $message
            ->action('Voir la demande', route('reservations.show', $r))
            ->salutation('Bénin Pétro — Gestion de flotte');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre' => $this->annulation ? 'Réservation annulée' : 'Demande non retenue',
            'message' => $this->reservation->code.' — '.($this->reservation->motif_refus ?? $this->reservation->motif_annulation ?? ''),
            'lien' => route('reservations.show', $this->reservation),
            'icone' => 'refus',
        ];
    }

    public function toSms(object $notifiable): void
    {
        if ($notifiable->telephone) {
            app(ServiceNotification::class)->envoyerSms(
                $notifiable->telephone,
                "Bénin Pétro : demande {$this->reservation->code} non retenue. Consultez la plateforme pour l'alternative proposée."
            );
        }
    }
}
