<?php

namespace Database\Seeders;

use App\Models\Agence;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        // Deux sites seulement : le siege et Parakou.
        Agence::firstOrCreate(
            ['nom' => 'Siège Bénin Pétro'],
            [
                'ville' => 'Cotonou',
                'adresse' => 'Boulevard de la Marina, Cotonou',
                'telephone' => '+229 21 00 00 00',
                'latitude' => 6.3654,
                'longitude' => 2.4183,
                'active' => true,
            ]
        );

        Agence::firstOrCreate(
            ['nom' => 'Parakou'],
            [
                'ville' => 'Parakou',
                'adresse' => 'Zone industrielle, Parakou',
                'telephone' => '+229 23 00 00 00',
                'latitude' => 9.3372,
                'longitude' => 2.6303,
                'active' => true,
            ]
        );

        $comptes = [
            [
                'matricule' => 'BP-ADM-001',
                'nom' => 'Administrateur',
                'prenom' => 'Bénin Pétro',
                'email' => 'admin@beninpetro.bj',
                'telephone' => '+22997000001',
                'poste' => 'Administrateur de la plateforme',
                'departement' => 'direction_systemes_information',
                'role' => 'administrateur',
            ],
            [
                'matricule' => 'BP-RF-001',
                'nom' => 'Dossou',
                'prenom' => 'Marc',
                'email' => 'responsable@beninpetro.bj',
                'telephone' => '+22997000002',
                'poste' => 'Responsable de flotte',
                'departement' => 'service_achats_logistique',
                'role' => 'responsable_flotte',
            ],
            [
                'matricule' => 'BP-COM-001',
                'nom' => 'Adjovi',
                'prenom' => 'Sylvie',
                'email' => 'commercial@beninpetro.bj',
                'telephone' => '+22997000003',
                'poste' => 'Chargée de clientèle',
                'departement' => 'direction_commerciale_marketing',
                'role' => 'commercial',
            ],
            [
                'matricule' => 'BP-COM-002',
                'nom' => 'Houngbo',
                'prenom' => 'Jean',
                'email' => 'commercial2@beninpetro.bj',
                'telephone' => '+22997000004',
                'poste' => 'Commercial terrain',
                'departement' => 'direction_superettes',
                'role' => 'commercial',
            ],
        ];

        foreach ($comptes as $compte) {
            $role = $compte['role'];
            unset($compte['role']);

            $utilisateur = User::firstOrCreate(
                ['email' => $compte['email']],
                array_merge($compte, [
                    'password' => Hash::make('BeninPetro2026!'),
                    'actif' => true,
                ])
            );

            $utilisateur->syncRoles([$role]);
        }
    }
}
