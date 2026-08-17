# Bénin Pétro — Plateforme de réservation et de gestion de flotte

Application web interne de **BENIN PETRO SA** permettant aux commerciaux de réserver un véhicule,
au responsable de flotte de valider et de piloter les missions, et à l'administrateur de gérer
comptes, permissions et paramétrage — le tout conformément au cahier des charges v3 (23/05/2026).

---

## Sommaire

- [Stack technique](#stack-technique)
- [Installation](#installation)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Configuration des clés API](#configuration-des-clés-api)
- [Tâches planifiées](#tâches-planifiées)
- [Rôles et permissions](#rôles-et-permissions)
- [Fonctionnalités couvertes](#fonctionnalités-couvertes)
- [Organisation du code](#organisation-du-code)
- [Mise en production](#mise-en-production)

---

## Stack technique

| Composant | Choix |
|---|---|
| Framework | Laravel 11 (PHP 8.2+) |
| Permissions | `spatie/laravel-permission` 6 |
| Interface | Blade + Tailwind CSS 3 + Alpine.js |
| Graphiques | Chart.js |
| Exports | `barryvdh/laravel-dompdf` (PDF), `maatwebsite/excel` (XLSX), CSV natif |
| Build | Vite |
| Base de données | MySQL 8 / MariaDB 10.6+ |

> Le cahier des charges évoquait une stack React / Hono / Supabase. Le projet a été réalisé en
> **Laravel + Blade + Tailwind**, conformément à l'orientation retenue en interne : un seul
> déploiement, pas d'API séparée à maintenir, et une base MySQL hébergée chez Bénin Pétro
> plutôt qu'un service tiers — ce qui simplifie aussi la conformité APDP sur les données chauffeurs.

---

## Installation

### Prérequis

- PHP **8.2** ou supérieur, avec les extensions `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`, `fileinfo`
- Composer 2
- Node.js 18+ et npm
- MySQL 8 ou MariaDB 10.6+

### Étapes

```bash
# 1. Dépendances
composer install
npm install

# 2. Environnement
cp .env.example .env
php artisan key:generate
```

Renseignez ensuite la connexion base de données dans `.env` :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=benin_petro_flotte
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 3. Schéma + jeu de données de démonstration
php artisan migrate --seed

# 4. Lien de stockage public (photos véhicules, chauffeurs, checklists)
php artisan storage:link

# 5. Compilation des assets
npm run build        # production
npm run dev          # développement (rechargement à chaud)

# 6. Lancement
php artisan serve
```

L'application est disponible sur <http://127.0.0.1:8000>.

### Réinitialiser complètement la base

```bash
php artisan migrate:fresh --seed
```

---

## Comptes de démonstration

Créés par `UtilisateurSeeder`. **Mot de passe commun : `BeninPetro2026!`**

| Rôle | E-mail | Accès |
|---|---|---|
| Administrateur | `admin@beninpetro.bj` | Tout, y compris paramètres, comptes et rôles |
| Responsable de flotte | `responsable@beninpetro.bj` | Validation, véhicules, chauffeurs, missions, rapports |
| Commercial | `commercial@beninpetro.bj` | Ses demandes et son historique |
| Commercial | `commercial2@beninpetro.bj` | Idem, pour tester le cloisonnement des données |

> **À faire avant la mise en production :** supprimer ou modifier ces comptes, et retirer
> `FlotteDemoSeeder` de `DatabaseSeeder` pour ne pas injecter les données fictives
> (10 véhicules, 6 chauffeurs, 24 réservations sur 60 jours).

---

## Configuration des clés API

Aucune clé n'est à écrire dans le code. Tout se pilote depuis
**Administration → Paramètres** (`/parametres`), accessible à l'administrateur.

Les clés sensibles sont **chiffrées en base** (`Crypt`) et ne sont jamais réaffichées en clair :
le champ montre uniquement les quatre derniers caractères. Un champ laissé vide conserve la
valeur existante ; un bouton *Effacer* permet de supprimer une clé.

### Google Maps

1. Console Google Cloud → sélectionnez ou créez un projet dédié.
2. Activez **Maps JavaScript API**, **Geocoding API** et **Distance Matrix API**.
3. Créez une clé dans *Identifiants*, puis restreignez-la au domaine de la plateforme.
4. Collez-la dans Paramètres → Intégrations, puis cliquez sur **Tester** : la plateforme géocode
   Cotonou pour valider la configuration.

Sans clé, l'application reste **pleinement fonctionnelle** : seules les cartes et l'estimation
automatique des distances sont masquées (la distance reste saisissable manuellement).

### Passerelle SMS

| Mode | Comportement |
|---|---|
| `log` | Les SMS sont écrits dans `storage/logs/laravel.log` — idéal en recette |
| `http` | POST vers l'URL du fournisseur, jeton transmis en `Bearer` |
| `desactive` | Aucun SMS n'est envoyé |

Les numéros sont normalisés au format international `+229`. Le bouton **Tester** envoie un SMS
au numéro renseigné dans votre profil.

### Autres paramètres modifiables sans redéploiement

- Notifications : activation e-mail / SMS, adresse en copie des alertes
- Règles de réservation : délai minimum avant départ, fenêtre d'annulation libre, délai de rappel
- Identité société : raison sociale, contacts, adresse (repris dans les e-mails et les PDF)

---

## Tâches planifiées

Deux commandes automatisent les notifications. Ajoutez **une seule ligne** au cron du serveur :

```cron
* * * * * cd /chemin/vers/le/projet && php artisan schedule:run >> /dev/null 2>&1
```

| Commande | Fréquence | Rôle |
|---|---|---|
| `flotte:rappels` | Toutes les heures | Rappel aux demandeurs et chauffeurs avant le départ |
| `flotte:alertes` | Chaque jour à 07:00 | Assurance, visite technique, révision, permis arrivant à échéance |

Test manuel :

```bash
php artisan flotte:rappels
php artisan flotte:alertes
```

---

## Rôles et permissions

Trois rôles métier sont créés par `RolePermissionSeeder`, à partir de **28 permissions**
réparties par module (`reservations.*`, `vehicules.*`, `rapports.*`, `parametres.*`, …).

| Rôle | Périmètre |
|---|---|
| `commercial` | Soumet des demandes, consulte **uniquement les siennes**, annule, évalue, déclare un litige |
| `responsable_flotte` | Valide / refuse avec motif et alternative, affecte véhicule et chauffeur, gère flotte, missions, checklists, maintenance, rapports |
| `administrateur` | Accès complet — conserve toujours l'intégralité des permissions par conception |

L'écran **Rôles et permissions** (`/roles`) permet de créer des profils sur mesure et de cocher
les permissions module par module. Les trois rôles métier ne sont pas supprimables.

---

## Fonctionnalités couvertes

### MVP (cahier des charges — phase 1)

- Authentification par compte sécurisé, **verrouillage temporaire après 5 tentatives** (5 min)
- Mot de passe provisoire à la création d'un compte, changement imposé à la première connexion
- Recherche et réservation : dates, lieux, type de véhicule, avec ou sans chauffeur, nombre de passagers
- **Vérification de disponibilité en temps réel** (AJAX) pendant la saisie
- Validation / refus **avec motif obligatoire et proposition d'alternative**
- Calendrier d'occupation de la flotte sur 14 jours, véhicule par véhicule
- Espace personnel et historique complet des réservations
- Notifications e-mail + base (cloche in-app avec compteur) et SMS optionnel :
  soumission, validation, refus, rappel de mission, alertes de flotte
- Gestion de flotte : véhicules, chauffeurs, sites, disponibilité, maintenance
- **Checklist avant ET après mission** — 4 rubriques, ~21 points de contrôle, photos,
  signature électronique tracée au doigt, taux de conformité calculé
- **Blocage de la clôture de mission** tant que les données sont incomplètes
  (checklists manquantes, kilométrage d'arrivée absent…), avec la liste des éléments à compléter
- Gestion des litiges avec suivi de statut et résolution, avis clients notés sur 5
- Journal d'activité horodaté (traçabilité, adresse IP)

### Phase 3 — Rapports

Section **entièrement générée par le serveur** : aucune saisie manuelle de rapport.

- Synthèse : réservations, taux de validation, délai moyen de validation, occupation, km, coûts
- Historique complet des réservations sur la période
- Taux d'occupation par véhicule (barre de progression + graphique)
- Rapport des checklists : conformité moyenne, anomalies relevées
- Coûts d'exploitation : carburant, frais de mission, maintenance, **coût au kilomètre**
- **Exports PDF, Excel et CSV** sur chaque rapport, avec en-tête aux couleurs de la charte

### Phase 2 — Géolocalisation

Le socle est en place (`ServiceGoogleMaps` : géocodage, distance, carte du trajet sur la fiche
réservation, coordonnées GPS des sites). Le **suivi temps réel des véhicules** reste à implémenter :
il suppose des boîtiers télématiques ou une application chauffeur, hors périmètre du MVP.

---

## Organisation du code

```
app/
├── Console/Commands/      flotte:rappels · flotte:alertes
├── Exports/               RapportExport (Excel)
├── Http/
│   ├── Controllers/       18 contrôleurs, un par domaine métier
│   └── Middleware/        VerifierCompteActif
├── Models/                12 modèles (règles métier dans les modèles)
├── Notifications/         5 notifications (mail + database + sms)
├── Providers/             directives Blade @dateFr @dateHeureFr @fcfa
└── Services/
    ├── ServiceKpi.php             calcul de tous les indicateurs
    ├── ServiceReservation.php     création, validation, refus, alternatives
    ├── ServiceGoogleMaps.php      géocodage et distances
    └── ServiceNotification.php    passerelle SMS configurable

config/beninpetro.php      règles métier, rubriques de checklist, rôles
database/
├── migrations/            16 migrations
└── seeders/               rôles, paramètres, utilisateurs, données de démo
lang/fr/                   validation, authentification, pagination
resources/views/           71 vues Blade, 11 composants réutilisables
routes/web.php             90 routes nommées, intégralement en français
```

### Conventions

- **Tout est en français** : routes, tables, colonnes, méthodes, variables, libellés.
- Les règles métier vivent dans les **modèles** (`peutEtreValidee()`, `elementsManquants()`,
  `alertes()`, `tauxOccupation()`) et les **services**, jamais dans les vues.
- Les composants Blade (`<x-carte>`, `<x-badge>`, `<x-champ>`, `<x-trajet>`…) garantissent
  l'homogénéité visuelle. `<x-trajet>` réutilise le chevron du logo comme flèche de direction.

### Charte graphique

Couleurs extraites du logo officiel :

| Usage | Code |
|---|---|
| Vert institutionnel (`petro-700`) | `#01582D` |
| Vert vif du chevron (`petro-400`) | `#01C96D` |
| Vert clair d'accent (`lime-400`) | `#9ADB5A` |

Polices : **Plus Jakarta Sans** (titres), **Inter** (texte), **JetBrains Mono** (immatriculations,
codes de réservation).

---

## Mise en production

```bash
composer install --optimize-autoloader --no-dev
npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

### Points de vigilance

- `APP_ENV=production` et `APP_DEBUG=false` dans `.env`
- La racine web du serveur doit pointer sur **`public/`**, jamais sur la racine du projet
- Droits d'écriture sur `storage/` et `bootstrap/cache/`
- Sauvegarde de `APP_KEY` : les clés API chiffrées deviennent **irrécupérables** sans elle
- Configurer le driver de mail (`MAIL_*`) pour les notifications
- HTTPS obligatoire : la plateforme manipule des données personnelles de chauffeurs
  (permis, coordonnées), soumises aux exigences de l'**APDP Bénin**

### Après un changement de permissions

```bash
php artisan permission:cache-reset
```

---

## État de la livraison

Le code a été écrit sans que PHP ni Composer soient disponibles dans l'environnement de
génération : **aucun fichier n'a pu être exécuté ni validé par l'interpréteur**. Les
vérifications réalisées portent sur la cohérence des références — routes, vues, permissions,
composants, équilibre des directives Blade — toutes sans anomalie.

Prévoyez donc une passe de recette après `composer install && php artisan migrate --seed`
pour lever les éventuelles coquilles résiduelles.
