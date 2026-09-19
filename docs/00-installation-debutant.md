# Guide d'installation — pour un développeur PHP qui découvre Laravel

Ce guide part du principe que vous savez développer en PHP (variables, fonctions, SQL...) mais
que vous n'avez **jamais utilisé Laravel**. Chaque étape explique non seulement *quoi taper*,
mais *pourquoi*, avec des parallèles avec du PHP "classique".

Pour la référence rapide (une fois à l'aise), voir le [README.md](../README.md) à la racine du
projet.

---

## 1. C'est quoi Laravel, en une page

Si vous avez déjà écrit du PHP "à la main" (fichiers `.php` avec `include`, requêtes SQL avec
`mysqli`/`PDO` directement dans la page, etc.), voici les équivalences :

| Ce que vous connaissez | L'équivalent Laravel | Le fichier/dossier concerné |
|---|---|---|
| Un fichier `.php` accessible par URL (`facture.php`) | Une **route** qui pointe vers du code | `routes/web.php` |
| Écrire du SQL à la main (`SELECT * FROM documents WHERE ...`) | **Eloquent** (un ORM) : `Document::where(...)->get()` génère le SQL pour vous | `app/Models/*.php` |
| Un script `install.sql` qu'on exécute une fois sur le serveur | Des **migrations** : des fichiers PHP versionnés qui décrivent la structure de la base, rejouables sur n'importe quel serveur | `database/migrations/` |
| Copier-coller du HTML avec des `<?php echo $x; ?>` | Des vues **Blade** (`{{ $x }}`), un moteur de templates | `resources/views/*.blade.php` |
| Un formulaire qui recharge toute la page à chaque clic | **Livewire** : des composants PHP qui se mettent à jour dynamiquement sans écrire de JavaScript | `app/Livewire/*.php` |
| `composer.json` d'une librairie tierce | Laravel *est* fourni via Composer, comme n'importe quel package | `composer.json` |
| Un script cron ou une tâche différée "maison" | Une **queue** (file d'attente) : des traitements lents (ici l'OCR) tournent en tâche de fond au lieu de bloquer la page | `php artisan queue:work` |
| Un fichier `config.php` avec vos identifiants de BDD en dur | Un fichier **`.env`** (jamais mis dans Git) + `config/*.php` qui le lit | `.env` |
| `php script.php` en ligne de commande | **`php artisan ...`** : la commande couteau-suisse de Laravel (comme un `composer run` mais fourni par le framework) | `artisan` |

Vous n'avez pas besoin de tout comprendre en détail avant de commencer : l'important est de
savoir que **`artisan` est votre outil principal** pour tout (créer la base, lancer le serveur,
etc.), un peu comme `composer` ou `npm` le sont dans d'autres écosystèmes.

---

## 2. Ce qu'il faut avoir installé sur votre machine

Vérifiez chaque outil avec la commande donnée. Si elle échoue, installez l'outil correspondant.

| Outil | Commande de vérification | Version minimale | Rôle |
|---|---|---|---|
| PHP | `php -v` | 8.3+ | Exécute le code de l'application |
| Composer | `composer --version` | 2.x | Télécharge les librairies PHP (équivalent de `npm` pour PHP) |
| PostgreSQL | `psql --version` | 14+ | La base de données |
| Node.js + npm | `node -v` et `npm -v` | Node 18+ | Compile le CSS (Tailwind) et le peu de JS nécessaire |
| Redis | `redis-cli ping` (doit répondre `PONG`) | 6+ | Cache + file d'attente (queue) |

### Extensions PHP nécessaires

Laravel a besoin de quelques extensions PHP courantes. Vérifiez avec :

```bash
php -m | grep -iE "pdo_pgsql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath"
```

Si `pdo_pgsql` manque (c'est la plus probable), c'est le pilote PHP pour PostgreSQL — sans lui,
Laravel ne peut pas du tout se connecter à la base. Sur Debian/Ubuntu : `sudo apt install
php-pgsql`. Sur macOS avec Homebrew, l'extension est généralement déjà incluse dans `php`.

### Installer PostgreSQL et Redis si vous ne les avez pas

**Ubuntu/Debian :**
```bash
sudo apt install postgresql redis-server
sudo service postgresql start
sudo service redis-server start
```

**macOS (Homebrew) :**
```bash
brew install postgresql redis
brew services start postgresql
brew services start redis
```

**Windows :** voir la section dédiée ci-dessous — l'installation directe sous Windows (sans WSL2)
pose régulièrement des problèmes de chemins et de permissions avec Composer et PostgreSQL.

---

## 2 bis. Installation sous Windows (WSL2)

La méthode recommandée n'installe **rien directement dans Windows** : elle utilise WSL2, un vrai
Linux (Ubuntu) qui tourne à l'intérieur de Windows, dans lequel vous exécutez exactement les
commandes Ubuntu/Debian de ce guide. C'est la façon la plus fiable de faire tourner un projet
Laravel sous Windows — la quasi-totalité des tutoriels Laravel supposent Linux/macOS.

### Étape 1 — Installer WSL2

Ouvrez **PowerShell en administrateur** (clic droit → "Exécuter en tant qu'administrateur") et
tapez :

```powershell
wsl --install
```

Cela installe WSL2 et une distribution Ubuntu par défaut. Redémarrez l'ordinateur si Windows le
demande. Au premier lancement d'Ubuntu (menu Démarrer → "Ubuntu"), on vous demande de créer un
nom d'utilisateur et un mot de passe **Unix** (rien à voir avec votre compte Windows) — choisissez
ce que vous voulez, vous vous en servirez pour `sudo`.

> Si `wsl --install` échoue en disant que la virtualisation n'est pas activée, il faut l'activer
> dans le BIOS/UEFI de la machine (option souvent nommée "Intel VT-x" ou "AMD-V") — cherchez
> "activer virtualisation [nom de votre PC]" si besoin.

### Étape 2 — Installer les outils dans Ubuntu

Ouvrez le terminal **Ubuntu** (pas PowerShell) depuis le menu Démarrer, et lancez :

```bash
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd unzip postgresql redis-server

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Démarrer les services
sudo service postgresql start
sudo service redis-server start
```

Vérifiez ensuite avec le tableau de la section 2 (`php -v`, `composer --version`, etc.) — tout
doit répondre correctement, **depuis le terminal Ubuntu**.

### Étape 3 — Récupérer le projet et l'éditer

Travaillez avec les fichiers **à l'intérieur du système de fichiers Linux** (pas dans
`/mnt/c/...`) pour éviter des lenteurs et des soucis de permissions — par exemple sous
`~/projets/` :

```bash
mkdir -p ~/projets && cd ~/projets
# copiez ou clonez le projet ici, puis :
cd Scolia/ged-pme
```

Pour éditer le code avec VS Code sous Windows tout en travaillant dans WSL : installez
l'extension **"WSL"** de Microsoft dans VS Code, puis depuis le terminal Ubuntu, dans le dossier
du projet :

```bash
code .
```

VS Code s'ouvre alors connecté directement au système de fichiers Linux (indiqué en bas à gauche
de la fenêtre : "WSL: Ubuntu").

### Étape 4 — Continuer normalement

À partir d'ici, suivez la suite de ce guide (sections 3 à 8) **dans le terminal Ubuntu** — toutes
les commandes (`composer install`, `php artisan migrate`, `php artisan serve`, etc.) sont
identiques à celles données pour Linux. Une fois `php artisan serve` lancé, ouvrez
`http://127.0.0.1:8000` dans votre navigateur Windows normal (Chrome, Edge...) : WSL2 rend les
ports automatiquement accessibles depuis Windows.

### Alternative sans WSL2 : Laragon

Si vous préférez une installation 100 % Windows sans ligne de commande Linux,
[Laragon](https://laragon.org/) fournit PHP, Composer et Node.js en quelques clics. Il n'inclut
en revanche pas PostgreSQL par défaut (seulement MySQL) : il faudrait installer PostgreSQL
séparément via [l'installeur officiel Windows](https://www.postgresql.org/download/windows/) et
faire de même pour Redis (moins direct sous Windows — voir
[Memurai](https://www.memurai.com/) comme alternative compatible Redis pour Windows, ou
[Redis via WSL2](https://redis.io/docs/latest/operate/oss_and_stack/install/install-redis/install-redis-on-windows/)
juste pour ce composant). **WSL2 reste la voie la plus simple** car elle évite de mélanger deux
écosystèmes différents.

---

## 3. Récupérer le projet

Le code de la GED vit dans le sous-dossier `ged-pme/` de ce dépôt (à côté de `backend/`, `web/`,
`mobile/` qui sont l'application Scolia — un produit différent). Toutes les commandes suivantes
s'exécutent **depuis `ged-pme/`** :

```bash
cd ged-pme
```

---

## 4. Installer les dépendances PHP (Composer)

```bash
composer install
```

**Ce que ça fait** : Composer lit `composer.json` (la liste des librairies dont le projet a
besoin — Laravel lui-même, Livewire, etc.) et télécharge tout dans un dossier `vendor/` qui
n'est jamais mis dans Git (trop volumineux, et reproductible à volonté par cette commande).
C'est l'exact équivalent de `npm install` côté JavaScript.

Si la commande échoue en demandant une version de PHP différente, vérifiez `php -v` : le projet
demande PHP 8.3 ou plus récent.

---

## 5. Configurer l'environnement (`.env`)

```bash
cp .env.example .env
php artisan key:generate
```

**`.env`** contient tout ce qui est spécifique à *votre* machine et qui ne doit jamais être
partagé/commité : mots de passe de base de données, clés d'API, etc. C'est pour ça qu'il est
dans `.gitignore`. Le fichier `.env.example` est un modèle sans les valeurs sensibles.

**`php artisan key:generate`** génère une clé secrète (`APP_KEY`) utilisée par Laravel pour
chiffrer les sessions et les cookies. Sans elle, l'application refuse de démarrer.

### Créer la base de données

```bash
sudo -u postgres createdb ged_pme
sudo -u postgres psql -c "CREATE USER ged_pme WITH PASSWORD 'changez-moi' CREATEDB;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ged_pme TO ged_pme;"
```

(Adaptez si vous avez déjà un utilisateur Postgres que vous préférez réutiliser.)

Puis ouvrez `.env` et vérifiez/complétez ces lignes (déjà présentes, juste à ajuster le mot de
passe) :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ged_pme
DB_USERNAME=ged_pme
DB_PASSWORD=changez-moi
```

> **Pourquoi PostgreSQL et pas MySQL ?** L'architecture (voir
> [`02-architecture-technique.md`](./02-architecture-technique.md)) utilise des fonctionnalités
> propres à Postgres : la Row-Level Security (une deuxième couche de sécurité qui isole les
> données de chaque entreprise cliente directement au niveau de la base), la recherche plein
> texte, et les colonnes JSON avancées (JSONB). Le projet ne fonctionnera pas avec MySQL.

Redis n'a normalement rien à configurer si vous l'avez installé avec les valeurs par défaut
(`REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`, déjà dans `.env`).

---

## 6. Créer les tables et les données de démonstration

```bash
php artisan migrate --seed
```

**Migrations** : chaque fichier dans `database/migrations/` décrit la création (ou
modification) d'une table, avec une date dans son nom pour l'ordre d'exécution. `migrate`
les exécute toutes dans l'ordre, sur la base vide que vous venez de créer. C'est l'équivalent
moderne d'un fichier `install.sql`, mais versionné et rejouable.

**`--seed`** exécute en plus les *seeders* (`database/seeders/`) : ils remplissent la base avec
des données de départ indispensables (le catalogue des permissions, les rôles système) et une
**entreprise de démonstration** prête à explorer.

Si cette commande échoue avec une erreur de connexion, revérifiez les valeurs `DB_*` dans
`.env` et que PostgreSQL tourne bien (`sudo service postgresql status`).

À la fin, un compte de test est disponible :

- **E-mail** : `admin@demo.test`
- **Mot de passe** : `password`

---

## 7. Compiler les assets front (CSS/JS)

```bash
npm install
npm run build
```

Le CSS de l'application est écrit avec Tailwind CSS et doit être "compilé" (transformé en un
seul fichier `.css` optimisé) avant utilisation — un peu comme on compilerait du SCSS. `npm run
build` fait ça une fois pour toutes (utile pour tester en local ou avant un déploiement).

> Pendant que vous développez activement (modifications fréquentes des vues), vous pouvez
> utiliser `npm run dev` à la place : il recompile automatiquement à chaque sauvegarde et
> rafraîchit la page.

---

## 8. Démarrer l'application

Il faut **deux processus séparés** qui tournent en même temps, dans deux terminaux différents :

**Terminal 1 — le serveur web** :
```bash
php artisan serve
```
Affiche une URL du type `http://127.0.0.1:8000` — c'est votre application, ouvrez-la dans un
navigateur.

**Terminal 2 — le worker de file d'attente** :
```bash
php artisan queue:work
```
Ce processus traite en arrière-plan les tâches lentes : extraction OCR d'un document scanné,
envoi des e-mails de notification. **Sans lui, l'upload fonctionne mais l'OCR et les e-mails ne
partiront jamais** — ils resteront simplement "en attente" indéfiniment. C'est volontaire dans
l'architecture : on ne fait jamais attendre l'utilisateur pendant qu'un fichier est analysé.

Connectez-vous avec `admin@demo.test` / `password`.

---

## 9. Se repérer dans le code (mini-glossaire du dossier)

```
ged-pme/
├── app/
│   ├── Domain/          → la logique métier "pure" (upload, workflow, archivage...),
│   │                       organisée par thème plutôt que par type technique
│   ├── Livewire/        → les composants d'interface interactifs (un par écran)
│   ├── Models/          → une classe par table de la base (Document, Folder, User...)
│   ├── Policies/        → "qui a le droit de faire quoi" sur chaque ressource
│   └── Http/Middleware/ → du code qui s'exécute avant chaque requête (ex. résoudre l'entreprise
│                           courante de l'utilisateur connecté)
├── database/
│   ├── migrations/      → la structure de la base, versionnée
│   └── seeders/         → les données de départ (rôles, permissions, démo)
├── resources/views/     → les templates Blade (le HTML), organisés comme les composants Livewire
├── routes/web.php       → la liste des URLs de l'application et ce qu'elles affichent
├── tests/               → les tests automatisés (voir section suivante)
├── config/              → la configuration (lit les valeurs depuis .env)
└── docs/                → toute la documentation d'architecture du produit (vous êtes ici)
```

---

## 10. Commandes utiles au quotidien

| Commande | À quoi ça sert |
|---|---|
| `php artisan list` | Affiche toutes les commandes disponibles (comme `--help` mais pour tout) |
| `php artisan tinker` | Une console interactive pour "jouer" avec le code (ex. `App\Models\Document::count()`) |
| `php artisan migrate:fresh --seed` | Repart d'une base **vide** et la recrée avec les données de démo (utile si vous avez mis le bazar en testant) |
| `php artisan route:list` | Liste toutes les URLs (routes) de l'application |
| `php artisan test` | Lance la suite de tests automatisés (voir plus bas) |

---

## 11. Lancer les tests automatisés

Les tests utilisent **une deuxième base de données**, dédiée, pour ne jamais toucher à vos
données de développement :

```bash
sudo -u postgres createdb ged_pme_testing
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ged_pme_testing TO ged_pme;"
php artisan test
```

Voir [`06-sauvegarde-plan-dev-tests.md`](./06-sauvegarde-plan-dev-tests.md) pour le détail de ce
qui est testé et pourquoi les tests tournent sur PostgreSQL plutôt que sur une base plus simple.

---

## 12. Problèmes fréquents

| Symptôme | Cause probable | Solution |
|---|---|---|
| `SQLSTATE[08006] could not connect to server` | PostgreSQL n'est pas démarré, ou mauvais `DB_HOST`/`DB_PORT` dans `.env` | `sudo service postgresql start`, revérifier `.env` |
| `No application encryption key has been specified` | `php artisan key:generate` pas exécuté | Lancez la commande (étape 5) |
| Page blanche ou erreur 500 | Souvent une erreur PHP masquée | Regardez `storage/logs/laravel.log`, la vraie erreur y est toujours |
| `Class "Redis" not found` | L'extension PHP `redis` n'est pas installée | `sudo apt install php-redis` (ou passez temporairement `REDIS_CLIENT=predis` dans `.env` après `composer require predis/predis`) |
| Les documents uploadés ne sont jamais indexés (OCR) | Le worker de queue (`php artisan queue:work`) n'est pas lancé | Ouvrez un second terminal, voir étape 8 |
| `permission denied for table ...` en base | La Row-Level Security (RLS) bloque l'accès car aucune entreprise n'est "active" dans la session | Normal si vous interrogez la base directement en dehors de l'application — utilisez `php artisan tinker` en passant par les modèles Eloquent, pas des requêtes SQL brutes |
| Port 8000 déjà utilisé | Une autre application tourne dessus | `php artisan serve --port=8080` |

---

## 12 bis. Monter YOSEFA comme lecteur réseau (WebDAV)

En plus de l'interface web, les dossiers et documents sont accessibles en WebDAV à l'adresse
`http://votre-serveur/webdav/` — pratique pour ouvrir/modifier un fichier Office directement
depuis l'explorateur de fichiers, sans passer par un téléchargement/upload manuel. Identifiants :
ceux du compte YOSEFA habituel (email + mot de passe).

- **Windows** : Poste de travail → "Ajouter un emplacement réseau" → entrez l'URL ci-dessus.
- **macOS** : Finder → `Cmd+K` (Se connecter au serveur) → entrez l'URL ci-dessus.

Un fichier ouvert et enregistré (Ctrl+S) depuis ce lecteur crée automatiquement une nouvelle
version du document dans YOSEFA (même contrôle antivirus et de type de fichier qu'un upload
classique). La création de dossiers et la suppression ne sont volontairement pas prises en charge
depuis ce lecteur réseau : utilisez l'interface web pour ces actions.

---

## 12 ter. Capture automatique des emails (optionnel)

Une boîte mail dédiée (ex. `depot@votreentreprise.com`) peut être surveillée automatiquement :
les pièces jointes des emails non lus sont déposées dans un dossier "à classer" de YOSEFA, sans
copie manuelle. Désactivée par défaut. Pour l'activer, ajoutez dans `.env` :

```
GED_EMAIL_CAPTURE_ENABLED=true
GED_EMAIL_CAPTURE_HOST=imap.exemple.com
GED_EMAIL_CAPTURE_PORT=993
GED_EMAIL_CAPTURE_USERNAME=depot@votreentreprise.com
GED_EMAIL_CAPTURE_PASSWORD=...
GED_EMAIL_CAPTURE_TARGET_FOLDER_ID=<uuid du dossier "à classer", visible dans son URL>
GED_EMAIL_CAPTURE_UPLOADER_USER_ID=<uuid de l'utilisateur technique, visible dans Admin > Utilisateurs>
```

Chaque pièce jointe passe par les mêmes contrôles qu'un dépôt manuel (extension, type, antivirus) ;
une pièce jointe rejetée est simplement ignorée. Vérification manuelle immédiate :
`php artisan ged:capture-emails`. En production, une tâche planifiée l'exécute déjà toutes les
5 minutes (voir `routes/console.php`) — rien à configurer côté planification.

---

## 13. Et après ?

- Pour comprendre *pourquoi* le projet est construit ainsi (multi-tenant, sécurité, workflow...),
  lisez [`docs/README.md`](./README.md) qui indexe tous les documents d'architecture.
- Pour un déploiement chez un client (pas juste en local), voir la section "Architecture de
  déploiement" dans [`02-architecture-technique.md`](./02-architecture-technique.md) — le
  projet est prévu pour tourner via Docker.
- La documentation officielle de Laravel ([laravel.com/docs](https://laravel.com/docs)) reste la
  meilleure ressource pour approfondir un concept rencontré dans le code (Eloquent, Blade,
  Livewire ont chacun leur propre documentation liée depuis là).
