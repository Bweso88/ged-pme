# GED PME

Plateforme de Gestion Électronique des Documents (GED) et d'archivage pour PME, cabinets et
associations — application Laravel MVP. Voir [`docs/`](./docs) pour l'architecture complète
(vision produit, modèle de données, sécurité, workflow, archivage, écrans, plan de tests...).

## Stack

Laravel 13 (PHP 8.4) · PostgreSQL · Redis (cache + queue) · Livewire 3 + Tailwind CSS 4 ·
Tesseract (OCR, optionnel).

## Installation locale

**Première fois avec Laravel ?** Suivez [`docs/00-installation-debutant.md`](./docs/00-installation-debutant.md) — un guide pas à pas qui explique chaque commande.

Pour un aller-retour rapide si vous connaissez déjà Laravel :

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurer dans `.env` : `DB_*` (une base PostgreSQL vide), `REDIS_*`. Puis :

```bash
php artisan migrate --seed   # crée le catalogue de permissions/rôles + une entreprise de démo
npm install && npm run build
php artisan serve
php artisan queue:work       # traite l'OCR et les notifications en tâche de fond
```

Compte de démonstration : `admin@demo.test` / `password`.

## Tests

Les tests tournent sur une vraie base PostgreSQL de test (`ged_pme_testing` par défaut, voir
`phpunit.xml`) — pas sqlite — car l'architecture s'appuie sur des fonctionnalités spécifiques à
Postgres (Row-Level Security multi-tenant, JSONB, recherche plein texte GIN, contrainte CHECK).

```bash
createdb ged_pme_testing   # une fois
php artisan test
```

## Points d'architecture clés

- **Multi-tenant** : isolation par `company_id` (Global Scope Eloquent) **et**, indépendamment,
  par Row-Level Security PostgreSQL — voir `app/Support/Tenancy/` et la migration
  `2024_01_01_000019_enable_row_level_security.php`.
- **Permissions** : RBAC par rôle + surcharges au niveau dossier/document
  (`App\Domain\Authorization\Services\PermissionChecker`).
- **Documents** : upload, versioning, métadonnées configurables, OCR asynchrone, corbeille,
  archivage/rétention, partage sécurisé — chacun dans son propre module sous `app/Domain/`.

Documentation complète : [`docs/README.md`](./docs/README.md).
