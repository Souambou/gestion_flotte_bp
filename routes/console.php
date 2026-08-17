<?php

use App\Console\Commands\EnvoyerRappelsDeplacements;
use App\Console\Commands\VerifierAlertesFlotte;
use Illuminate\Support\Facades\Schedule;

Schedule::command(EnvoyerRappelsDeplacements::class)->hourly();
Schedule::command(VerifierAlertesFlotte::class)->dailyAt('07:00');
