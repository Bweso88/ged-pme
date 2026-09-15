# GED PME — Architecture technique, multi-tenant, API, stockage, déploiement

> Livrables couverts : 5. Architecture technique — 6. Architecture multi-tenant — 9. Architecture API — 10. Architecture stockage — 17. Architecture de déploiement

---

## 5. Architecture technique globale

```
                         ┌───────────────────────────┐
                         │        Navigateur          │
                         │  (Blade + Livewire + JS)   │
                         └──────────────┬─────────────┘
                                        │ HTTPS
                         ┌──────────────▼─────────────┐
                         │           Nginx             │
                         │   (TLS, reverse proxy)      │
                         └──────────────┬─────────────┘
                                        │
                         ┌──────────────▼─────────────┐
                         │      Application Laravel     │
                         │  Controllers / Livewire /    │
                         │  API REST (Sanctum)          │
                         │  Policies (RBAC) / Services  │
                         └───┬───────────┬─────────┬────┘
                             │           │         │
                    ┌────────▼───┐ ┌─────▼───┐ ┌───▼─────────┐
                    │ PostgreSQL │ │  Redis  │ │  Stockage    │
                    │ (données)  │ │(cache+  │ │ Local / S3   │
                    │            │ │ queue)  │ │ (fichiers)   │
                    └────────────┘ └────┬────┘ └─────────────┘
                                         │
                                ┌────────▼────────┐
                                │  Worker(s) Queue  │
                                │  (jobs: OCR,       │
                                │  notifications,     │
                                │  rétention, audit)   │
                                └────────┬────────┘
                                         │
                                ┌────────▼────────┐
                                │ Moteur OCR        │
                                │ (Tesseract local   │
                                │  ou API externe)   │
                                └────────────────────┘
```

### 5.1 Justification des choix technologiques (section 29 du cahier des charges)

| Choix | Justification |
|---|---|
| **Laravel (PHP)** | Écosystème mature (Eloquent ORM, migrations, queues, policies, Sanctum), très large bassin de développeurs PHP (cohérent avec votre profil et le marché du freelancing/agences PME), permet un déploiement mutualisé low-cost chez la plupart des hébergeurs. |
| **PostgreSQL** | Row-Level Security natif utile en défense en profondeur multi-tenant, JSONB performant pour métadonnées dynamiques et texte OCR, contraintes fortes, extension `pg_trgm` pour recherche approximative. Cohérent avec la stack déjà choisie côté Scolia. |
| **Blade + Livewire** | Permet une UI réactive (upload, recherche, workflow) sans construire une API séparée pour chaque écran, réduit la surface de code à maintenir pour une petite équipe. React reste possible en V2 pour des écrans à forte interactivité (ex. visualiseur de documents avancé). |
| **API REST (Sanctum)** | Nécessaire pour le mobile (Flutter V2), les intégrations tierces (ERP, comptabilité) et l'app mobile web. Sanctum = tokens simples, pas de complexité OAuth inutile pour une PME. |
| **Redis** | Sert à la fois de cache applicatif et de driver de queue (jobs OCR, notifications, calculs de rétention) — un seul composant à opérer pour deux besoins. |
| **Tesseract (OCR)** | Gratuit, open-source, fonctionne offline (pas de dépendance à un service cloud tiers pour un premier déploiement PME sensible). Architecture par interface (`OcrEngine`) permettant de brancher une API cloud (Google Vision, AWS Textract) en V2 sans changer le code appelant. |
| **Nginx** | Standard, performant pour servir les assets et faire reverse-proxy PHP-FPM, gère bien le téléchargement de gros fichiers avec `X-Accel-Redirect` pour ne jamais exposer les chemins de stockage réels. |
| **Docker** | Reproductibilité du déploiement chez chaque client (PME sans compétence DevOps), montée en charge facilitée, isolation des dépendances (Tesseract, PHP, Postgres, Redis) sans polluer le serveur hôte. |

### 5.2 Découpage applicatif (modulaire)

```
app/
├── Domain/
│   ├── Tenancy/          # Entreprises, résolution du tenant courant
│   ├── Documents/        # Document, DocumentVersion, Folder, Metadata
│   ├── Workflow/         # WorkflowDefinition, WorkflowStep, WorkflowInstance
│   ├── Archiving/        # RetentionPolicy, ArchiveRecord
│   ├── Sharing/          # DocumentShare
│   ├── Audit/            # AuditLog (append-only)
│   ├── Notifications/    # canaux in-app / email
│   └── Ocr/              # contrat OcrEngine + implémentation Tesseract
├── Http/
│   ├── Controllers/Web   # pages Blade/Livewire
│   ├── Controllers/Api   # API REST
│   └── Middleware/       # ResolveTenant, EnsureTenantIsolation
├── Livewire/             # composants UI réactifs
├── Policies/             # autorisations par ressource
└── Jobs/                 # OCR, calcul rétention, notifications différées
```

Chaque domaine expose des **Services** (logique métier) appelés par les contrôleurs/Livewire — jamais de logique métier dans les vues, jamais d'accès direct au modèle depuis une vue sans passer par une policy.

---

## 6. Architecture multi-tenant

### 6.1 Stratégie retenue : **base de données partagée, isolation par ligne (row-level multi-tenancy)**

Trois stratégies existent : bases séparées par client, schémas séparés (PostgreSQL `search_path`), ou table partagée avec `company_id`. Pour ce produit :

| Stratégie | Avantages | Inconvénients | Retenue ? |
|---|---|---|---|
| DB par client | Isolation maximale, sauvegarde/restauration indépendante | Coût opérationnel élevé au-delà de quelques dizaines de clients, migrations à rejouer N fois | Non pour le SaaS mutualisé, **oui** pour les déploiements "on-premise dédiés" vendus à 3 000 € |
| Schéma PostgreSQL par client | Bon compromis isolation/coût | Complexifie les migrations Laravel, outillage moins standard | Non |
| Table partagée + `company_id` + RLS | Simple à opérer, scalable à plusieurs centaines de clients, coût d'infra mutualisé | Isolation applicative à sécuriser rigoureusement | **Oui, pour l'offre SaaS mutualisée** |

**Décision** : le code est écrit pour fonctionner dans les deux modes de déploiement sans divergence :
1. **Déploiement dédié PME** (l'offre à 3 000 €) : une instance = une base = une entreprise (`company_id` unique, toujours le même). C'est le mode par défaut vendu aux PME qui veulent "leur" GED.
2. **Déploiement SaaS mutualisé** (revenu récurrent, plusieurs centaines d'entreprises) : même code, une seule instance, isolation par `company_id` sur chaque table métier.

### 6.2 Mécanisme d'isolation

- Toutes les tables métier portent une colonne `company_id` (non nullable, indexée, clé étrangère vers `companies`).
- Un **Global Scope Eloquent** (`BelongsToCompany`) est appliqué automatiquement à chaque modèle métier : toute requête est filtrée par le tenant courant, sans possibilité d'oubli côté développeur.
- Le tenant courant est résolu par un middleware `ResolveTenant` (à partir de l'utilisateur authentifié, jamais depuis un paramètre d'URL/formulaire modifiable côté client) et stocké dans un singleton `TenantContext` pour la durée de la requête.
- **Défense en profondeur PostgreSQL** : Row-Level Security (RLS) activé sur les tables sensibles, avec une policy `USING (company_id = current_setting('app.current_company_id')::uuid)`. Même en cas de bug applicatif oubliant le scope, la base refuse de renvoyer les lignes d'un autre tenant.
- Les fichiers physiques sont eux aussi préfixés par tenant : `storage/app/tenants/{company_uuid}/documents/...` — aucune convention de nommage ne permet de deviner le chemin d'un autre client.
- Le Super Administrateur opère dans un contexte spécifique (`admin.` sous-domaine ou zone `/superadmin`) qui ne traverse **jamais** le scope tenant : il gère uniquement la table `companies` et des métriques agrégées, jamais le contenu documentaire des clients.

### 6.3 Cycle de vie d'une entreprise (tenant)

`companies` : `id`, `nom`, `slug`, `logo_path`, `couleur_primaire`, `plan`, `statut` (actif/suspendu/résilié), `date_creation`, `quota_stockage_mo`, `parametres` (JSONB : structure documentaire par défaut, langue, fuseau horaire).

Onboarding : Super Admin crée l'entreprise → compte Administrateur entreprise initial créé → email d'invitation → configuration guidée (structure de dossiers, premiers utilisateurs, logo).

---

## 9. Architecture API

### 9.1 Principes

- API REST versionnée : `/api/v1/...`
- Authentification par token (Laravel Sanctum), un token par appareil/session (mobile, intégration tierce).
- Toutes les routes API passent par les mêmes Policies que l'interface web (pas de logique d'autorisation dupliquée).
- Pagination systématique (cursor ou page-based) sur les listes.
- Réponses au format JSON:API-like simplifié : `{ "data": ..., "meta": ..., "links": ... }`.
- Rate limiting par utilisateur/IP (`throttle` Laravel) pour se protéger du brute force et de l'exfiltration massive.

### 9.2 Ressources principales

```
POST   /api/v1/auth/login
POST   /api/v1/auth/logout

GET    /api/v1/folders
POST   /api/v1/folders
GET    /api/v1/folders/{id}

GET    /api/v1/documents?folder_id=&q=&type=&statut=&date_from=&date_to=
POST   /api/v1/documents                      (upload, multipart)
GET    /api/v1/documents/{id}
PATCH  /api/v1/documents/{id}                 (renommer, métadonnées)
DELETE /api/v1/documents/{id}                 (corbeille)
POST   /api/v1/documents/{id}/restore
DELETE /api/v1/documents/{id}/force           (admin uniquement)

GET    /api/v1/documents/{id}/versions
POST   /api/v1/documents/{id}/versions        (nouvelle version)
POST   /api/v1/documents/{id}/versions/{v}/restore

POST   /api/v1/documents/{id}/shares
DELETE /api/v1/documents/{id}/shares/{shareId}
GET    /api/v1/shares/{token}                 (accès public contrôlé, sans authentification)

POST   /api/v1/documents/{id}/workflow/submit
POST   /api/v1/documents/{id}/workflow/approve
POST   /api/v1/documents/{id}/workflow/reject
POST   /api/v1/documents/{id}/workflow/request-changes

POST   /api/v1/documents/{id}/archive
GET    /api/v1/archives?statut=&categorie=

GET    /api/v1/audit-logs?document_id=&user_id=&action=&date_from=&date_to=

GET    /api/v1/notifications
POST   /api/v1/notifications/{id}/read

GET    /api/v1/dashboard/summary
```

### 9.3 Téléchargement sécurisé des fichiers

Aucune route ne renvoie une URL de stockage brute. `GET /api/v1/documents/{id}/download` :
1. vérifie la policy (droit "Télécharger"),
2. journalise l'accès dans l'audit,
3. stream le fichier via `Storage::response()` (local) ou une **URL S3 pré-signée à courte durée de vie** (5 minutes) si stockage objet — jamais un lien permanent public.

---

## 10. Architecture de stockage

### 10.1 Séparation stricte données / fichiers

- **PostgreSQL** : métadonnées, structure, droits, workflows, audit — jamais le contenu binaire des fichiers.
- **Filesystem abstrait** (Laravel `Storage` avec disks) : contenu binaire des documents.

### 10.2 Disks supportés (via configuration, sans changement de code)

| Disk | Usage cible |
|---|---|
| `local` | Déploiement PME mono-serveur (offre 3 000 €), simple, pas de dépendance externe |
| `nas` (SMB/NFS monté) | PME avec NAS existant, même driver `local` pointant vers le point de montage |
| `s3` (compatible S3 : AWS S3, OVH, Scaleway, MinIO) | Offre SaaS mutualisée / clients avec forte volumétrie ou besoin de scalabilité |

### 10.3 Convention de stockage

```
{disk_root}/tenants/{company_uuid}/documents/{document_uuid}/v{n}/{sha256}.{ext}
{disk_root}/tenants/{company_uuid}/thumbnails/{document_uuid}.jpg
{disk_root}/tenants/{company_uuid}/tmp/{upload_uuid}   (nettoyé après traitement)
```

- Nom de fichier physique **jamais** dérivé du nom d'origine (anti path traversal, anti collision).
- Hash SHA-256 du contenu stocké en base → détection d'intégrité (le fichier n'a pas été altéré depuis son dépôt) et détection de doublons.
- Chaque version d'un document est un fichier distinct : aucune écrasement silencieux.

### 10.4 Trajectoire de montée en charge

1. **Palier 1 (MVP, 1 client)** : disque local du serveur, sauvegarde rsync/borg quotidienne.
2. **Palier 2 (croissance, volumétrie)** : bascule transparente vers un disque S3-compatible (changement de configuration uniquement, aucune ligne de code applicative à modifier grâce à l'abstraction `Storage`).
3. **Palier 3 (SaaS, centaines de clients)** : stockage objet avec cycle de vie automatisé (classes de stockage froides pour les archives anciennes), CDN pour les miniatures.

---

## 17. Architecture de déploiement

### 17.1 Docker Compose (déploiement PME standard)

```yaml
services:
  app:        # PHP-FPM + code Laravel
  nginx:      # reverse proxy TLS (Let's Encrypt via certbot ou traefik)
  worker:     # php artisan queue:work (OCR, notifications, rétention)
  scheduler:  # cron Laravel (php artisan schedule:run) — calcul des échéances, purge tmp
  postgres:   # base de données
  redis:      # cache + queue
```

- Un seul `docker-compose.yml` + fichier `.env` par client → déploiement reproductible en quelques heures.
- Mises à jour applicatives : `git pull` + `php artisan migrate` + rebuild image, testé sur environnement de staging avant chaque client.

### 17.2 Topologie réseau

```
Internet ──TLS──> Nginx (443) ──> PHP-FPM (interne, non exposé)
                                └─> Postgres (interne, non exposé, port fermé côté firewall)
                                └─> Redis (interne, non exposé)
```

- Aucun port base de données/Redis exposé publiquement.
- Fichiers de stockage local montés en volume Docker, **hors** de la racine web servie par Nginx (jamais accessible via une URL directe).

### 17.3 Environnements

`local` (développement) → `staging` (validation avant mise en prod client) → `production` (client). Variables d'environnement isolées, jamais de secret commité (utilisation de `.env` + gestionnaire de secrets pour le SaaS mutualisé).
