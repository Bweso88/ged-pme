# YOSEFA — Schéma de base de données et diagramme des relations

> Livrables couverts : 7. Schéma de base de données — 8. Diagramme des relations

Toutes les tables métier (hors `companies`, `jobs`, `sessions`, `cache`) portent une colonne `company_id UUID NOT NULL` avec index et RLS PostgreSQL (voir doc 02, §6.2).

## 7.1 Tables principales

```sql
-- ============ TENANCY ============
companies (
  id UUID PK,
  nom VARCHAR,
  slug VARCHAR UNIQUE,
  logo_path VARCHAR NULL,
  couleur_primaire VARCHAR NULL,
  plan VARCHAR DEFAULT 'starter',          -- starter, business, enterprise
  statut VARCHAR DEFAULT 'actif',          -- actif, suspendu, resilie
  quota_stockage_mo INTEGER DEFAULT 10240,
  parametres JSONB DEFAULT '{}',
  created_at, updated_at
)

-- ============ IDENTITÉ / DROITS ============
users (
  id UUID PK,
  company_id UUID FK -> companies,          -- NULL pour le Super Admin plateforme
  nom VARCHAR, email VARCHAR UNIQUE, password_hash VARCHAR,
  statut VARCHAR DEFAULT 'actif',           -- actif, suspendu
  derniere_connexion_at TIMESTAMP NULL,
  tentatives_echouees INTEGER DEFAULT 0,
  verrouille_jusqu_a TIMESTAMP NULL,
  created_at, updated_at
)

roles (id UUID PK, company_id UUID NULL, nom VARCHAR, is_system BOOLEAN)
-- rôles système : super_admin, admin_entreprise, responsable_documentaire,
--                 manager, employe, lecteur

permissions (id UUID PK, code VARCHAR UNIQUE)
-- ex: document.view, document.download, document.edit, document.share,
--     document.validate, document.archive, document.delete, folder.manage,
--     admin.users, admin.settings, ...

role_permissions (role_id FK, permission_id FK, PRIMARY KEY(role_id, permission_id))
user_roles (user_id FK, role_id FK, PRIMARY KEY(user_id, role_id))

user_groups (id UUID PK, company_id UUID FK, nom VARCHAR)
user_group_members (group_id FK, user_id FK, PRIMARY KEY(group_id, user_id))

services (                                  -- "Direction", "RH", "Finance"...
  id UUID PK, company_id UUID FK,
  nom VARCHAR, parent_id UUID NULL FK -> services  -- arborescence configurable
)

-- ============ ESPACE DOCUMENTAIRE ============
folders (
  id UUID PK, company_id UUID FK,
  parent_id UUID NULL FK -> folders,
  service_id UUID NULL FK -> services,
  nom VARCHAR, chemin_materialise VARCHAR,  -- ex: /entreprise/rh/contrats (perf. recherche)
  created_by UUID FK -> users,
  created_at, updated_at
)

document_types (                            -- "Contrat", "Facture", "Note de frais"...
  id UUID PK, company_id UUID FK,
  nom VARCHAR, code VARCHAR,
  duree_conservation_mois INTEGER NULL,     -- valeur par défaut, cf. retention_policies
  created_at
)

metadata_fields (                           -- définition configurable des métadonnées
  id UUID PK, company_id UUID FK,
  document_type_id UUID NULL FK -> document_types,  -- NULL = champ global
  code VARCHAR, label VARCHAR,
  type VARCHAR,                             -- texte, nombre, date, liste, booleen
  options JSONB NULL,                       -- valeurs possibles si type=liste
  obligatoire BOOLEAN DEFAULT false,
  ordre INTEGER DEFAULT 0
)

documents (
  id UUID PK, company_id UUID FK,
  folder_id UUID FK -> folders,
  document_type_id UUID NULL FK -> document_types,
  nom VARCHAR,
  reference VARCHAR NULL,
  statut VARCHAR DEFAULT 'brouillon',       -- brouillon, soumis, en_validation, publie, archive
  confidentialite VARCHAR DEFAULT 'interne', -- public_entreprise, restreint, confidentiel
  auteur_id UUID FK -> users,
  proprietaire_id UUID FK -> users,
  mots_cles TEXT[] NULL,
  date_document DATE NULL,
  date_expiration DATE NULL,
  version_courante_id UUID NULL FK -> document_versions,
  is_trashed BOOLEAN DEFAULT false,
  trashed_at TIMESTAMP NULL,
  trashed_by UUID NULL FK -> users,
  created_at, updated_at
)

document_metadata_values (
  document_id UUID FK -> documents,
  metadata_field_id UUID FK -> metadata_fields,
  valeur TEXT,
  PRIMARY KEY (document_id, metadata_field_id)
)

document_versions (
  id UUID PK, company_id UUID FK,
  document_id UUID FK -> documents,
  numero_version INTEGER,                   -- 1, 2, 3...
  storage_path VARCHAR,
  taille_octets BIGINT,
  hash_sha256 VARCHAR,
  mime_type VARCHAR,
  texte_ocr TEXT NULL,                      -- résultat OCR, indexé (full-text/GIN)
  ocr_statut VARCHAR DEFAULT 'non_requis',  -- non_requis, en_attente, termine, echec
  auteur_id UUID FK -> users,
  commentaire VARCHAR NULL,
  created_at
)
-- Index full-text : CREATE INDEX ON document_versions USING GIN (to_tsvector('french', texte_ocr));

-- ============ WORKFLOW & VALIDATION ============
workflow_definitions (
  id UUID PK, company_id UUID FK,
  nom VARCHAR, document_type_id UUID NULL FK -> document_types,
  actif BOOLEAN DEFAULT true
)

workflow_steps (
  id UUID PK, workflow_definition_id UUID FK,
  ordre INTEGER,
  nom VARCHAR,                              -- "Validation manager", "Validation direction"
  role_requis_id UUID NULL FK -> roles,      -- qui peut valider cette étape
  user_requis_id UUID NULL FK -> users       -- ou une personne nommée
)

workflow_instances (
  id UUID PK, company_id UUID FK,
  document_id UUID FK -> documents,
  workflow_definition_id UUID FK,
  etape_courante_id UUID NULL FK -> workflow_steps,
  statut VARCHAR DEFAULT 'en_cours',         -- en_cours, termine, rejete, annule
  created_at, updated_at
)

workflow_actions (                           -- = journal des validations
  id UUID PK, company_id UUID FK,
  workflow_instance_id UUID FK,
  workflow_step_id UUID FK,
  utilisateur_id UUID FK -> users,
  action VARCHAR,                            -- approuve, rejete, demande_modification, commentaire
  commentaire TEXT NULL,
  created_at                                 -- horodatage immuable
)

-- ============ ARCHIVAGE & RÉTENTION ============
retention_policies (
  id UUID PK, company_id UUID FK,
  document_type_id UUID FK -> document_types,
  duree_conservation_mois INTEGER,
  action_a_expiration VARCHAR,                -- conserver, proposer_destruction, transferer_archive, demander_validation
  categorie_archive VARCHAR NULL,
  actif BOOLEAN DEFAULT true
)

archive_records (
  id UUID PK, company_id UUID FK,
  document_id UUID FK -> documents,
  date_archivage TIMESTAMP,
  categorie VARCHAR NULL,
  confidentialite VARCHAR,
  motif VARCHAR NULL,
  date_destruction_prevue DATE NULL,
  statut VARCHAR DEFAULT 'actif',             -- actif, propose_destruction, valide_destruction, detruit
  valide_par UUID NULL FK -> users,
  valide_at TIMESTAMP NULL,
  created_at
)

-- ============ PARTAGE ============
document_shares (
  id UUID PK, company_id UUID FK,
  document_id UUID FK -> documents,
  cree_par UUID FK -> users,
  type VARCHAR,                               -- utilisateur, groupe, lien
  cible_user_id UUID NULL FK -> users,
  cible_group_id UUID NULL FK -> user_groups,
  token VARCHAR NULL UNIQUE,                  -- pour type=lien
  mot_de_passe_hash VARCHAR NULL,
  expire_at TIMESTAMP NULL,                   -- obligatoire pour type=lien
  revoque BOOLEAN DEFAULT false,
  created_at
)

-- ============ CORBEILLE ============
-- géré via documents.is_trashed / trashed_at / trashed_by (soft delete étendu)
-- suppression définitive = purge physique + entrée audit_logs dédiée, jamais de CASCADE silencieux

-- ============ AUDIT ============
audit_logs (
  id UUID PK, company_id UUID FK,
  utilisateur_id UUID NULL FK -> users,
  action VARCHAR,                             -- connexion, deconnexion, creation, consultation,
                                               -- telechargement, modification, deplacement,
                                               -- partage, suppression, restauration,
                                               -- validation, archivage, changement_permission
  ressource_type VARCHAR,                     -- document, folder, user, role, ...
  ressource_id UUID NULL,
  ip_adresse VARCHAR NULL,
  details JSONB NULL,
  created_at                                  -- table append-only, jamais de UPDATE/DELETE applicatif
)

-- ============ NOTIFICATIONS ============
notifications (
  id UUID PK, company_id UUID FK,
  utilisateur_id UUID FK -> users,
  type VARCHAR,                               -- nouveau_document, a_valider, rejete, approuve,
                                               -- commentaire, expiration_proche, workflow_attente, partage_recu
  donnees JSONB,
  lu_at TIMESTAMP NULL,
  created_at
)
```

## 8. Diagramme des relations (ERD simplifié)

```
companies 1───* users
companies 1───* services 1───* services (auto, arborescence)
companies 1───* folders  1───* folders  (auto, arborescence)
services  1───* folders

roles *───* permissions   (role_permissions)
users *───* roles         (user_roles)
users *───* user_groups   (user_group_members)

folders   1───* documents
document_types 1───* documents
document_types 1───* metadata_fields
documents  1───* document_metadata_values ──* metadata_fields
documents  1───* document_versions
documents  1───1 document_versions          (version_courante_id)

document_types 1───* workflow_definitions
workflow_definitions 1───* workflow_steps
documents  1───* workflow_instances ──1 workflow_definitions
workflow_instances 1───* workflow_actions ──1 workflow_steps
workflow_actions   *───1 users

document_types 1───* retention_policies
documents  1───* archive_records

documents  1───* document_shares
users      1───* document_shares (cree_par)

users      1───* audit_logs
documents  1───* notifications (via ressource_id, lien logique non-FK)
```

### Notes de conception

- **UUID partout** (et non des auto-increment entiers) : évite toute énumération d'identifiants entre tenants et facilite une future fusion/migration entre bases.
- **`audit_logs` est append-only** : aucune route applicative n'expose de UPDATE/DELETE sur cette table ; seule une politique de purge après N années (configurable, ex. 10 ans) est autorisée via une tâche planifiée documentée.
- **`document_versions.texte_ocr`** indexé en full-text PostgreSQL (`tsvector`) permet la recherche décrite en section 8/9 du cahier des charges sans dépendance à un moteur externe pour le MVP ; migration vers Meilisearch/Elasticsearch possible en V2 en changeant seulement la couche recherche (Laravel Scout).
- **Contrainte d'intégrité métier** : un `document_share` de type `lien` a une contrainte applicative (et un `CHECK` SQL) imposant `expire_at IS NOT NULL` — interdiction des liens publics permanents (section 19 du cahier des charges).
