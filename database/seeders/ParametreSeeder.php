<?php

namespace Database\Seeders;

use App\Models\Parametre;
use Illuminate\Database\Seeder;

class ParametreSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [
            // --- Intégrations (clés API configurables depuis l'interface)
            ['cle' => 'google_maps_api_key', 'groupe' => 'integrations', 'libelle' => 'Clé API Google Maps', 'type' => 'password', 'chiffre' => true,
                'description' => 'Active la carte des trajets, le géocodage des lieux et l\'estimation des distances. Activez Maps JavaScript, Geocoding et Distance Matrix sur votre projet Google Cloud.'],
            ['cle' => 'google_maps_actif', 'groupe' => 'integrations', 'libelle' => 'Afficher les cartes dans l\'application', 'type' => 'boolean', 'chiffre' => false, 'valeur' => '1',
                'description' => 'Désactivez pour masquer les cartes sans supprimer la clé.'],
            ['cle' => 'sms_fournisseur', 'groupe' => 'integrations', 'libelle' => 'Passerelle SMS', 'type' => 'select', 'chiffre' => false, 'valeur' => 'log',
                'description' => 'log = journalisation locale (développement) · http = passerelle externe · desactive = aucun SMS.'],
            ['cle' => 'sms_api_url', 'groupe' => 'integrations', 'libelle' => 'URL de la passerelle SMS', 'type' => 'text', 'chiffre' => false,
                'description' => 'Point d\'entrée HTTP POST de votre fournisseur SMS.'],
            ['cle' => 'sms_api_key', 'groupe' => 'integrations', 'libelle' => 'Clé API SMS', 'type' => 'password', 'chiffre' => true,
                'description' => 'Jeton d\'authentification transmis en Bearer.'],
            ['cle' => 'sms_sender_id', 'groupe' => 'integrations', 'libelle' => 'Nom d\'expéditeur SMS', 'type' => 'text', 'chiffre' => false, 'valeur' => 'BENINPETRO',
                'description' => '11 caractères maximum.'],

            // --- Notifications
            ['cle' => 'notif_email_active', 'groupe' => 'notifications', 'libelle' => 'Notifications par e-mail', 'type' => 'boolean', 'chiffre' => false, 'valeur' => '1'],
            ['cle' => 'notif_sms_active', 'groupe' => 'notifications', 'libelle' => 'Notifications par SMS', 'type' => 'boolean', 'chiffre' => false, 'valeur' => '0'],
            ['cle' => 'notif_email_copie', 'groupe' => 'notifications', 'libelle' => 'Adresse en copie des alertes', 'type' => 'text', 'chiffre' => false,
                'description' => 'Boîte générique recevant une copie des alertes de flotte.'],

            // --- Règles de réservation
            ['cle' => 'delai_minimum_heures', 'groupe' => 'reservation', 'libelle' => 'Délai minimum avant départ (heures)', 'type' => 'number', 'chiffre' => false, 'valeur' => '2'],
            ['cle' => 'delai_annulation_heures', 'groupe' => 'reservation', 'libelle' => 'Annulation libre jusqu\'à (heures avant départ)', 'type' => 'number', 'chiffre' => false, 'valeur' => '24'],
            ['cle' => 'rappel_avant_heures', 'groupe' => 'reservation', 'libelle' => 'Rappel automatique avant départ (heures)', 'type' => 'number', 'chiffre' => false, 'valeur' => '24'],

            // --- Général
            ['cle' => 'societe_nom', 'groupe' => 'general', 'libelle' => 'Raison sociale', 'type' => 'text', 'chiffre' => false, 'valeur' => 'Bénin Pétro SA'],
            ['cle' => 'societe_email', 'groupe' => 'general', 'libelle' => 'E-mail de contact', 'type' => 'text', 'chiffre' => false, 'valeur' => 'flotte@beninpetro.bj'],
            ['cle' => 'societe_email_qhse', 'groupe' => 'notifications', 'libelle' => 'Adresse du service QHSE', 'type' => 'text', 'chiffre' => false, 'valeur' => 'qhse@beninpetro.bj', 'description' => 'Chaque litige déclaré y est transmis automatiquement.'],
            ['cle' => 'societe_telephone', 'groupe' => 'general', 'libelle' => 'Téléphone', 'type' => 'text', 'chiffre' => false, 'valeur' => '+229 21 00 00 00'],
            ['cle' => 'societe_adresse', 'groupe' => 'general', 'libelle' => 'Adresse du siège', 'type' => 'textarea', 'chiffre' => false, 'valeur' => 'Cotonou, République du Bénin'],
        ];

        foreach ($parametres as $parametre) {
            Parametre::firstOrCreate(['cle' => $parametre['cle']], $parametre);
        }
    }
}
