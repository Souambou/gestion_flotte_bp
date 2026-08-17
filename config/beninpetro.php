<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identite de l'application
    |--------------------------------------------------------------------------
    */
    'societe' => [
        'nom' => 'Bénin Pétro SA',
        'logo' => 'images/logo-benin-petro-blanc.png',
        'couleur_primaire' => '#01582D',
        'couleur_accent' => '#01C96D',
        'email' => 'flotte@beninpetro.bj',
        'email_qhse' => 'qhse@beninpetro.bj',
        'telephone' => '+229 21 00 00 00',
        'adresse' => 'Cotonou, République du Bénin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Regles metier
    |--------------------------------------------------------------------------
    */
    'reservation' => [
        // Aucun delai minimal : une demande peut etre soumise pour un depart imminent.
        'delai_minimum_heures' => 0,
        // Delai (en heures) avant le depart au-dela duquel l'annulation est libre
        'delai_annulation_heures' => 24,
        // Duree maximale d'une reservation en jours
        'duree_max_jours' => 30,
        // Rappel automatique avant le depart (en heures)
        'rappel_avant_heures' => 24,
    ],

    'maintenance' => [
        // Alerte quand la prochaine revue est a moins de X jours
        'alerte_jours' => 15,
        // Alerte quand il reste moins de X km avant la revue
        'alerte_km' => 1000,
        // Alerte permis chauffeur expirant sous X jours
        'alerte_permis_jours' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Points de controle de la checklist etat du vehicule
    |--------------------------------------------------------------------------
    | Utilises pour generer dynamiquement le formulaire du controle matinal
    | realise chaque matin sur chaque vehicule de la flotte.
    */
    'checklist' => [
        'Extérieur' => [
            'carrosserie' => 'Carrosserie (rayures, chocs)',
            'pare_brise' => 'Pare-brise et vitres',
            'pneus' => 'État des pneus',
            'roue_secours' => 'Roue de secours',
            'feux' => 'Feux et clignotants',
            'retroviseurs' => 'Rétroviseurs',
        ],
        'Intérieur' => [
            'proprete' => 'Propreté habitacle',
            'sieges' => 'État des sièges',
            'climatisation' => 'Climatisation',
            'tableau_bord' => 'Voyants tableau de bord',
        ],
        'Mécanique & fluides' => [
            'niveau_huile' => 'Niveau d\'huile moteur',
            'liquide_frein' => 'Liquide de frein',
            'liquide_refroidissement' => 'Liquide de refroidissement',
            'batterie' => 'Batterie',
            'freins' => 'Freins',
        ],
        'Sécurité & documents' => [
            'triangle' => 'Triangle de signalisation',
            'extincteur' => 'Extincteur',
            'trousse_secours' => 'Trousse de secours',
            'carte_grise' => 'Carte grise',
            'assurance' => 'Attestation d\'assurance',
            'visite_technique' => 'Visite technique',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Departements de la societe
    |--------------------------------------------------------------------------
    | Renseigne par le demandeur sur chaque reservation : c'est la cle des
    | statistiques par service attendues dans les rapports.
    | La cle est un identifiant technique interne, jamais affiche.
    | Chaque service porte deux libelles :
    |   - libelle : nom complet, utilise partout ou l'espace le permet
    |               (formulaire de reservation, fiche detaillee, listes) ;
    |   - sigle   : forme courte, reservee aux rapports et aux exports ou
    |               les colonnes sont nombreuses et l'espace compte.
    | Passer par App\Support\Departements plutot que de lire ce tableau
    | directement : les deux formes y restent synchronisees.
    */
    'departements' => [
        'direction_commerciale_marketing' => [
            'libelle' => 'Direction Commerciale et Marketing',
            'sigle' => 'DCM',
        ],
        'direction_administrative_financiere' => [
            'libelle' => 'Direction Administrative et Financière',
            'sigle' => 'DAF',
        ],
        'direction_superettes' => [
            'libelle' => 'Direction des Supérettes',
            'sigle' => 'DS',
        ],
        'direction_systemes_information' => [
            'libelle' => 'Direction des Systèmes d\'Information',
            'sigle' => 'DSI',
        ],
        'service_technique' => [
            'libelle' => 'Service Technique',
            'sigle' => 'ST',
        ],
        'service_achats_logistique' => [
            'libelle' => 'Service Achats et Logistique',
            'sigle' => 'SAL',
        ],
        'centres_emplisseurs_gpl' => [
            'libelle' => 'Centres Emplisseurs GPL',
            'sigle' => 'CEG',
        ],
        'service_juridique' => [
            'libelle' => 'Service Juridique',
            'sigle' => 'JUR',
        ],
        'qhse' => [
            'libelle' => 'QHSE',
            'sigle' => 'QHSE',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nature du deplacement
    |--------------------------------------------------------------------------
    */
    'types_deplacement' => [
        'sortie_simple' => 'Sortie simple',
        'mission' => 'Mission',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles applicatifs
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'administrateur' => 'Administrateur',
        'responsable_flotte' => 'Responsable de flotte',
        'commercial' => 'Commercial',
    ],
];
