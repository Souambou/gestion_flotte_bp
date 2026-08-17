<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\RappelDeplacement;
use App\Support\Notificateur;
use Illuminate\Console\Command;

class EnvoyerRappelsDeplacements extends Command
{
    protected $signature = 'flotte:rappels';

    protected $description = 'Envoie les rappels de départ aux commerciaux et responsables (à planifier toutes les heures)';

    public function handle(): int
    {
        $fenetre = (int) config('beninpetro.reservation.rappel_avant_heures');

        $reservations = Reservation::where('statut', 'validee')
            ->whereBetween('date_debut', [now(), now()->addHours($fenetre)])
            ->whereDoesntHave('deplacement', fn ($q) => $q->where('statut', '!=', 'planifiee'))
            ->with('demandeur')
            ->get();

        foreach ($reservations as $reservation) {
            Notificateur::envoyer($reservation->demandeur, new RappelDeplacement($reservation));
        }

        $this->info("{$reservations->count()} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
