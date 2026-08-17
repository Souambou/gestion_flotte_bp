<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Envoi de notifications qui ne fait jamais echouer l'operation metier.
 *
 * Une reservation enregistree doit le rester meme si le serveur SMTP est
 * injoignable ou si la passerelle SMS repond mal. L'incident est journalise
 * dans storage/logs, l'utilisateur poursuit son travail.
 */
class Notificateur
{
    /** Notifie un destinataire unique. */
    public static function envoyer($destinataire, Notification $notification): void
    {
        if (! $destinataire) {
            return;
        }

        try {
            $destinataire->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Notification non delivree', [
                'destinataire' => $destinataire->id ?? null,
                'notification' => $notification::class,
                'erreur' => $e->getMessage(),
            ]);
        }
    }

    /** Notifie une collection de destinataires, un echec n'interrompt pas les suivants. */
    public static function envoyerA(iterable $destinataires, Notification $notification): void
    {
        foreach ($destinataires as $destinataire) {
            static::envoyer($destinataire, $notification);
        }
    }
}
