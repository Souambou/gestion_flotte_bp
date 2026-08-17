<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Catalogue des permissions, regroupees par module.
     */
    public const PERMISSIONS = [
        'reservations.creer',
        'reservations.consulter',
        'reservations.voir_toutes',
        'planning.consulter',
        'reservations.modifier',
        'reservations.valider',
        'reservations.annuler',
        'deplacements.consulter',
        'deplacements.gerer',
        'checklists.remplir',
        'vehicules.consulter',
        'vehicules.creer',
        'vehicules.modifier',
        'vehicules.supprimer',
        'chauffeurs.consulter',
        'chauffeurs.creer',
        'chauffeurs.modifier',
        'chauffeurs.supprimer',
        'maintenances.consulter',
        'maintenances.gerer',
        'litiges.declarer',
        'litiges.traiter',
        'rapports.consulter',
        'rapports.exporter',
        'utilisateurs.consulter',
        'utilisateurs.gerer',
        'roles.gerer',
        'agences.gerer',
        'parametres.gerer',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // --- Commercial (client interne) : soumet et suit ses propres demandes.
        $commercial = Role::firstOrCreate(['name' => 'commercial', 'guard_name' => 'web']);
        $commercial->syncPermissions([
            'reservations.creer',
            'reservations.consulter',
            'planning.consulter',
            'litiges.declarer',
        ]);

        // --- Responsable de flotte : pilote la flotte et valide les demandes.
        $responsable = Role::firstOrCreate(['name' => 'responsable_flotte', 'guard_name' => 'web']);
        $responsable->syncPermissions([
            'reservations.creer',
            'reservations.consulter',
            'reservations.voir_toutes',
            'reservations.modifier',
            'reservations.valider',
            'reservations.annuler',
            'planning.consulter',
            'deplacements.consulter',
            'deplacements.gerer',
            'checklists.remplir',
            'vehicules.consulter',
            'vehicules.creer',
            'vehicules.modifier',
            'vehicules.supprimer',
            'chauffeurs.consulter',
            'chauffeurs.creer',
            'chauffeurs.modifier',
            'maintenances.consulter',
            'maintenances.gerer',
            'litiges.declarer',
            'litiges.traiter',
            'rapports.consulter',
            'rapports.exporter',
        ]);

        // --- Administrateur : toutes les actions du responsable + configuration.
        $administrateur = Role::firstOrCreate(['name' => 'administrateur', 'guard_name' => 'web']);
        $administrateur->syncPermissions(Permission::all());
    }
}
