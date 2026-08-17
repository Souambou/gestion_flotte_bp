<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Services\ServiceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationSoumise extends Notification
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
                ->line("La demande {$r->code} de ".($r->demandeur?->nom_complet ?? 'un collaborateur')." a été annulée.")
                ->line("Trajet : {$r->trajet}")
                ->action('Consulter la demande', route('reservations.show', $r));
        }

        return (new MailMessage)
            ->subject("Nouvelle demande de réservation — {$r->code}")
            ->greeting('Bonjour '.$notifiable->prenom.',')
            ->line("{$r->demandeur->nom_complet} a soumis une demande de véhicule.")
            ->line("Trajet : {$r->trajet}")
            ->line('Période : du '.$r->date_debut->format('d/m/Y H:i').' au '.$r->date_fin->format('d/m/Y H:i'))
            ->line($r->avec_chauffeur ? 'Avec chauffeur.' : 'Sans chauffeur.')
            ->action('Traiter la demande', route('reservations.show', $r))
            ->salutation('Bénin Pétro — Gestion de flotte');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre' => $this->annulation ? 'Réservation annulée' : 'Nouvelle demande de réservation',
            'message' => $this->reservation->code.' — '.$this->reservation->trajet,
            'lien' => route('reservations.show', $this->reservation),
            'icone' => $this->annulation ? 'annulation' : 'demande',
        ];
    }

    public function toSms(object $notifiable): void
    {
        if ($notifiable->telephone) {
            app(ServiceNotification::class)->envoyerSms(
                $notifiable->telephone,
                "Bénin Pétro : nouvelle demande {$this->reservation->code} à traiter."
            );
        }
    }
}
