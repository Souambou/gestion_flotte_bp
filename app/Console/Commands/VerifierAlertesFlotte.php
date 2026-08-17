<?php

namespace App\Console\Commands;

use App\Models\Chauffeur;
use App\Models\User;
use App\Models\Vehicule;
use App\Notifications\AlerteFlotte;
use App\Support\Notificateur;
use Illuminate\Console\Command;

class VerifierAlertesFlotte extends Command
{
    protected $signature = 'flotte:alertes';

    protected $description = 'Contrôle quotidien : maintenances dues, assurances, visites techniques et permis expirants';

    public function handle(): int
    {
        $destinataires = User::actifs()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['administrateur', 'responsable_flotte']))
            ->get();

        if ($destinataires->isEmpty()) {
            $this->warn('Aucun destinataire actif pour les alertes.');

            return self::SUCCESS;
        }

        $compteur = 0;

        foreach (Vehicule::whereNotIn('statut', ['hors_service'])->get() as $vehicule) {
            foreach ($vehicule->alertes() as $alerte) {
                Notificateur::envoyerA($destinataires, new AlerteFlotte(
                    "Véhicule {$vehicule->immatriculation}",
                    $alerte['message'],
                    route('vehicules.show', $vehicule),
                    $alerte['niveau'],
                ));
                $compteur++;
            }
        }

        $seuil = (int) config('beninpetro.maintenance.alerte_permis_jours');

        foreach (Chauffeur::whereNotNull('date_expiration_permis')->get() as $chauffeur) {
            if ($chauffeur->date_expiration_permis->lte(now()->addDays($seuil))) {
                Notificateur::envoyerA($destinataires, new AlerteFlotte(
                    "Permis — {$chauffeur->nom_complet}",
                    'Expiration le '.$chauffeur->date_expiration_permis->format('d/m/Y'),
                    route('chauffeurs.show', $chauffeur),
                    $chauffeur->permis_expire ? 'danger' : 'attention',
                ));
                $compteur++;
            }
        }

        $this->info("{$compteur} alerte(s) diffusée(s).");

        return self::SUCCESS;
    }
}
