<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte generique de flotte : maintenance due, assurance ou permis expirant,
 * checklist incomplete bloquant une cloture de déplacement.
 */
class AlerteFlotte extends Notification
{
    use Queueable;

    public function __construct(
        public string $titre,
        public string $message,
        public ?string $lien = null,
        public string $niveau = 'attention',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Alerte flotte — {$this->titre}")
            ->greeting('Bonjour '.$notifiable->prenom.',')
            ->line($this->message);

        if ($this->lien) {
            $mail->action('Ouvrir la plateforme', $this->lien);
        }

        return $mail->salutation('Bénin Pétro — Gestion de flotte');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre' => $this->titre,
            'message' => $this->message,
            'lien' => $this->lien,
            'icone' => 'alerte',
            'niveau' => $this->niveau,
        ];
    }
}
