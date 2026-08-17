<?php

use App\Http\Controllers\AgenceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\ChauffeurController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LitigeController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\DeplacementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\VehiculeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes web — Bénin Pétro, gestion de flotte
|--------------------------------------------------------------------------
| Toute la plateforme est derrière authentification : commercial,
| responsable de flotte et administrateur accèdent via un compte sécurisé.
*/

Route::redirect('/', '/tableau-de-bord');

// ---------------------------------------------------------------- Authentification
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'formulaire'])->name('connexion');
    Route::post('/connexion', [AuthController::class, 'connexion'])->middleware('throttle:10,1');
});

Route::post('/deconnexion', [AuthController::class, 'deconnexion'])->middleware('auth')->name('deconnexion');

// ---------------------------------------------------------------- Application
Route::middleware(['auth', 'compte.actif'])->group(function () {

    Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------------------------------------------ Profil
    Route::prefix('mon-compte')->name('profil.')->group(function () {
        Route::get('/', [ProfilController::class, 'edit'])->name('edit');
        Route::put('/', [ProfilController::class, 'update'])->name('update');
        Route::get('/mot-de-passe', [ProfilController::class, 'formulaireMotDePasse'])->name('mot-de-passe');
        Route::put('/mot-de-passe', [ProfilController::class, 'changerMotDePasse'])->name('mot-de-passe.update');
    });

    // ------------------------------------------------------------ Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/compteur', [NotificationController::class, 'compteur'])->name('compteur');
        Route::get('/{id}', [NotificationController::class, 'ouvrir'])->name('ouvrir');
        Route::post('/tout-lire', [NotificationController::class, 'toutMarquerLu'])->name('tout-lire');
    });

    // ------------------------------------------------------------ Réservations
    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/nouvelle', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}', [ReservationController::class, 'show'])->name('show');
        Route::get('/{reservation}/modifier', [ReservationController::class, 'edit'])->name('edit');
        Route::put('/{reservation}', [ReservationController::class, 'update'])->name('update');
        Route::post('/{reservation}/valider', [ReservationController::class, 'valider'])->name('valider');
        Route::post('/{reservation}/refuser', [ReservationController::class, 'refuser'])->name('refuser');
        Route::post('/{reservation}/annuler', [ReservationController::class, 'annuler'])->name('annuler');
        Route::post('/{reservation}/avis', [AvisController::class, 'store'])->name('avis');
    });

    // ------------------------------------------------------------ Planning de la flotte (ouvert a tous)
    Route::get('/planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::get('/planning/disponibilite', [PlanningController::class, 'disponibilite'])->name('planning.disponibilite');

    // ------------------------------------------------------------ Déplacements & checklists
    Route::prefix('deplacements')->name('deplacements.')->group(function () {
        Route::get('/', [DeplacementController::class, 'index'])->name('index');
        Route::get('/{deplacement}', [DeplacementController::class, 'show'])->name('show');
        Route::post('/{deplacement}/demarrer', [DeplacementController::class, 'demarrer'])->name('demarrer');
        Route::post('/{deplacement}/cloturer', [DeplacementController::class, 'cloturer'])->name('cloturer');
        Route::post('/{deplacement}/incident', [DeplacementController::class, 'signalerIncident'])->name('incident');
    });

    // ------------------------------------------------------------ Controle matinal de la flotte
    Route::prefix('checklists')->name('checklists.')->group(function () {
        Route::get('/', [ChecklistController::class, 'index'])->name('index');
        Route::get('/vehicule/{vehicule}', [ChecklistController::class, 'create'])->name('create');
        Route::post('/vehicule/{vehicule}', [ChecklistController::class, 'store'])->name('store');
        Route::get('/{checklist}', [ChecklistController::class, 'show'])->name('show');
    });

    // ------------------------------------------------------------ Flotte
    Route::resource('vehicules', VehiculeController::class)->parameters(['vehicules' => 'vehicule']);
    Route::post('/vehicules/{vehicule}/statut', [VehiculeController::class, 'changerStatut'])->name('vehicules.statut');

    Route::resource('chauffeurs', ChauffeurController::class);
    Route::resource('maintenances', MaintenanceController::class)->except('show');
    Route::resource('agences', AgenceController::class)->except('show');

    // ------------------------------------------------------------ Litiges & avis
    Route::resource('litiges', LitigeController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::get('/avis', [AvisController::class, 'index'])->name('avis.index');

    // ------------------------------------------------------------ Rapports (générés par le serveur)
    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::get('/', [RapportController::class, 'index'])->name('index');
        Route::get('/historique', [RapportController::class, 'historique'])->name('historique');
        Route::get('/occupation', [RapportController::class, 'occupation'])->name('occupation');
        Route::get('/departements', [RapportController::class, 'departements'])->name('departements');
        Route::get('/kilometrage', [RapportController::class, 'kilometrage'])->name('kilometrage');
        Route::get('/checklists', [RapportController::class, 'checklists'])->name('checklists');
        Route::get('/couts', [RapportController::class, 'couts'])->name('couts');
        Route::get('/{rapport}/export/{format}', [RapportController::class, 'exporter'])->name('export');
    });

    // ------------------------------------------------------------ Administration
    Route::resource('utilisateurs', UtilisateurController::class)->parameters(['utilisateurs' => 'utilisateur']);
    Route::post('/utilisateurs/{utilisateur}/activation', [UtilisateurController::class, 'basculerActivation'])->name('utilisateurs.activation');
    Route::post('/utilisateurs/{utilisateur}/mot-de-passe', [UtilisateurController::class, 'reinitialiserMotDePasse'])->name('utilisateurs.mot-de-passe');

    Route::resource('roles', RoleController::class)->except('show');

    Route::prefix('parametres')->name('parametres.')->group(function () {
        Route::get('/', [ParametreController::class, 'index'])->name('index');
        Route::put('/', [ParametreController::class, 'update'])->name('update');
        Route::delete('/cle/{cle}', [ParametreController::class, 'effacerCle'])->name('cle.effacer');
        Route::post('/test-maps', [ParametreController::class, 'testerMaps'])->name('test-maps');
        Route::post('/test-sms', [ParametreController::class, 'testerSms'])->name('test-sms');
    });
});
