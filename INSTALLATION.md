# Installation — Plateforme Bénin Pétro

Guide pas à pas pour démarrer le projet depuis zéro sur une machine Windows,
dans le terminal de VS Code (**Ctrl + `** pour l'ouvrir).

---

## 1. Vérifier les prérequis

Lancez ces trois commandes. Chacune doit afficher un numéro de version.

```bash
php -v
composer -V
node -v
```

Il vous faut **PHP 8.2 ou plus**, **Composer 2**, **Node.js 18 ou plus**.
Si PHP n'est pas reconnu, démarrez Laragon, XAMPP ou WampServer et ajoutez son
dossier `php` au `PATH` de Windows.

Vérifiez aussi que les extensions PHP suivantes sont activées dans votre `php.ini` :
`pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`, `fileinfo`, `openssl`.

---

## 2. Ouvrir le projet

Décompressez l'archive, puis dans VS Code : **Fichier → Ouvrir le dossier** et
sélectionnez `benin-petro-flotte`.

Toutes les commandes qui suivent se tapent dans le terminal intégré, à la racine
du projet (là où se trouvent `artisan` et `composer.json`).

---

## 3. Installer les dépendances

```bash
composer install
```

```bash
npm install
```

Comptez quelques minutes la première fois. Ces deux commandes créent les dossiers
`vendor/` et `node_modules/`, absents de l'archive car trop volumineux.

---

## 4. Créer le fichier de configuration

```bash
copy .env.example .env
```

```bash
php artisan key:generate
```

> `php artisan key:generate` génère la clé de chiffrement de l'application.
> **Sauvegardez-la** : les clés API enregistrées plus tard dans la plateforme
> sont chiffrées avec elle et deviennent irrécupérables si vous la perdez.

---

## 5. Créer la base de données

Dans phpMyAdmin (ou HeidiSQL, ou en ligne de commande), créez une base **vide** :

```sql
CREATE DATABASE benin_petro_flotte CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Ouvrez ensuite `.env` dans VS Code et ajustez ces lignes :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=benin_petro_flotte
DB_USERNAME=root
DB_PASSWORD=
```

Vérifiez au passage que la ligne suivante est bien présente — c'est elle qui
évite l'erreur de connexion SMTP en recette :

```dotenv
MAIL_MAILER=log
```

Les e-mails seront alors écrits dans `storage/logs/laravel.log` au lieu d'être
réellement envoyés. Aucun serveur de messagerie n'est nécessaire pour tester.

---

## 6. Créer les tables et les données de démonstration

```bash
php artisan migrate --seed
```

Cette commande crée les 16 tables, les 3 rôles, les 28 permissions, les comptes
utilisateurs et un jeu de démonstration (10 véhicules, 6 chauffeurs,
24 réservations réparties sur 60 jours).

---

## 7. Rendre les fichiers accessibles

```bash
php artisan storage:link
```

Crée le lien symbolique qui rend visibles les photos de véhicules, de chauffeurs
et de checklists.

> Sous Windows, cette commande peut échouer sans droits administrateur. Dans ce
> cas, ouvrez VS Code en tant qu'administrateur et relancez-la.

---

## 8. Compiler les feuilles de style

```bash
npm run build
```

---

## 9. Démarrer

```bash
php artisan serve
```

Ouvrez **http://127.0.0.1:8000**

---

## Comptes de démonstration

Mot de passe commun : **`BeninPetro2026!`**

| Rôle | E-mail |
|---|---|
| Administrateur | `admin@beninpetro.bj` |
| Responsable de flotte | `responsable@beninpetro.bj` |
| Commercial | `commercial@beninpetro.bj` |
| Commercial | `commercial2@beninpetro.bj` |

Le second commercial permet de vérifier le cloisonnement : connecté avec l'un,
vous ne voyez jamais les demandes de l'autre.

---

## Récapitulatif — toutes les commandes à la suite

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

(La création de la base et l'édition de `.env` s'intercalent entre
`key:generate` et `migrate --seed`.)

---

## Pendant le développement

Ouvrez **deux terminaux** dans VS Code (bouton `+` du panneau Terminal) :

| Terminal 1 | Terminal 2 |
|---|---|
| `php artisan serve` | `npm run dev` |

`npm run dev` recompile automatiquement à chaque sauvegarde d'un fichier CSS ou
Blade. Plus confortable que de relancer `npm run build` à la main.

Arrêter un serveur : **Ctrl + C** dans son terminal.

---

## Le réflexe en cas de comportement bizarre

Après avoir remplacé un fichier PHP, modifié `.env` ou changé une route :

```bash
php artisan optimize:clear
```

Cette commande vide les caches de configuration, de routes et de vues compilées.
Elle résout la grande majorité des « j'ai modifié le fichier mais rien ne change ».

---

## Problèmes courants

| Symptôme | Cause probable | Solution |
|---|---|---|
| `SQLSTATE[HY000] [1049] Unknown database` | La base n'existe pas | Créez-la (étape 5) |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL n'est pas démarré | Lancez MySQL dans Laragon/XAMPP |
| `Connection could not be established with host` | `MAIL_MAILER=smtp` sans serveur | Mettez `MAIL_MAILER=log` puis `php artisan optimize:clear` |
| La page s'affiche sans aucun style | Assets non compilés | `npm run build` |
| Les images ne s'affichent pas | Lien manquant | `php artisan storage:link` |
| `No application encryption key` | Clé absente | `php artisan key:generate` |
| Une modification reste invisible | Cache | `php artisan optimize:clear` |
| `Class ... not found` après une copie de fichiers | Autoloader obsolète | `composer dump-autoload` |

---

## Avant la mise en production

```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

Et surtout :

- `APP_ENV=production` et `APP_DEBUG=false` dans `.env`
- La racine web du serveur doit pointer sur **`public/`**, jamais sur la racine du projet
- Supprimez les 4 comptes de démonstration
- Retirez `FlotteDemoSeeder` de `database/seeders/DatabaseSeeder.php`
- Passez `MAIL_MAILER=smtp` et renseignez le serveur de messagerie réel
- Activez HTTPS : la plateforme traite des données personnelles de chauffeurs,
  soumises aux exigences de l'APDP Bénin
- Ajoutez la tâche planifiée au cron du serveur :

```cron
* * * * * cd /chemin/vers/le/projet && php artisan schedule:run >> /dev/null 2>&1
```
