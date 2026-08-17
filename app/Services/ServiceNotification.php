<?php

namespace App\Services;

use App\Models\Parametre;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Passerelle SMS configurable depuis Parametres > Integrations.
 * Le fournisseur "log" ecrit simplement le message dans les logs (mode developpement).
 */
class ServiceNotification
{
    public function envoyerSms(string $numero, string $message): bool
    {
        $fournisseur = Parametre::valeur('sms_fournisseur', config('services.sms.provider', 'log'));

        if ($fournisseur === 'desactive') {
            return false;
        }

        if ($fournisseur === 'log') {
            Log::info("[SMS -> {$numero}] {$message}");

            return true;
        }

        $url = Parametre::valeur('sms_api_url');
        $cle = Parametre::valeur('sms_api_key');
        $expediteur = Parametre::valeur('sms_sender_id', 'BENINPETRO');

        if (! $url || ! $cle) {
            Log::warning('Passerelle SMS non configurée : message non envoyé.');

            return false;
        }

        try {
            $reponse = Http::timeout(10)
                ->withToken($cle)
                ->post($url, [
                    'to' => $this->normaliserNumero($numero),
                    'from' => $expediteur,
                    'message' => $message,
                ]);

            return $reponse->successful();
        } catch (\Throwable $e) {
            Log::error('Échec envoi SMS : '.$e->getMessage());

            return false;
        }
    }

    protected function normaliserNumero(string $numero): string
    {
        $numero = preg_replace('/[^0-9+]/', '', $numero);

        if (! str_starts_with($numero, '+')) {
            $numero = '+229'.ltrim($numero, '0');
        }

        return $numero;
    }
}
