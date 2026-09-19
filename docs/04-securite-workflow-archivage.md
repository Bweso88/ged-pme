# YOSEFA — Modèle de sécurité, matrice des permissions, workflow documentaire, politique d'archivage

> Livrables couverts : 11. Modèle de sécurité — 12. Matrice rôles/permissions — 13. Workflow documentaire — 14. Politique d'archivage

---

## 11. Modèle de sécurité

### 11.1 Défense en profondeur — couches

| Couche | Mesures |
|---|---|
| Réseau | HTTPS obligatoire (redirection forcée), TLS 1.2+, HSTS, ports DB/Redis fermés en externe |
| Authentification | Hash Argon2id (via `password_hash` Laravel), politique de mot de passe configurable, limitation des tentatives (5 échecs → verrouillage temporaire, cf. `users.tentatives_echouees`), 2FA optionnel (TOTP) en V2, expiration de session configurable |
| Autorisation | RBAC (rôles + permissions granulaires) + policies Laravel appliquées à **chaque** action, jamais de contrôle uniquement côté UI |
| Application | Protection CSRF (tokens Laravel natifs sur tout formulaire/Livewire), échappement systématique (Blade échappe par défaut → anti-XSS), requêtes paramétrées via Eloquent/Query Builder (anti-injection SQL), validation stricte des entrées (Form Requests) |
| Fichiers | Vérification du type MIME réel (pas seulement l'extension), whitelist d'extensions autorisées, limite de taille configurable par entreprise, scan antivirus optionnel (ClamAV, hook avant indexation) |
| Accès fichiers | Aucune URL de stockage directe/prévisible ; tout téléchargement passe par un contrôleur authentifié + policy + journalisation |
| Multi-tenant | Isolation applicative (Global Scope) + isolation base (Row-Level Security PostgreSQL) — voir doc 02 |
| Auditabilité | Journal d'audit exhaustif, append-only |
| Sauvegarde | Sauvegardes chiffrées, testées régulièrement (voir doc 06) |
| Chiffrement | Chiffrement au repos pour les champs sensibles (ex. mots de passe de partage, tokens) via `encrypted` cast Laravel ; chiffrement du disque de stockage recommandé au niveau infrastructure pour les documents confidentiels |

### 11.2 Détails techniques clés

- **Contrôle d'extension et de type** : validation double — extension whitelist ET détection du type MIME réel via `finfo` (évite qu'un `.php` renommé en `.pdf` soit accepté).
- **Anti path traversal** : noms de fichiers physiques générés (UUID), jamais dérivés du nom d'origine fourni par l'utilisateur.
- **Limitation de taille** : configurable par entreprise (`companies.parametres.taille_max_mo`), vérifiée côté serveur (jamais uniquement côté client).
- **Sessions** : durée de vie configurable, invalidation à la déconnexion, régénération de l'identifiant de session à la connexion (anti session fixation).
- **CSRF** : automatique sur toutes les routes web Laravel/Livewire ; les routes API utilisent des tokens Sanctum (pas de cookies de session côté API tierce).
- **Antivirus** : point d'extension `AntivirusScanner` (interface), implémentation par défaut "no-op" documentée comme telle, implémentation ClamAV branchable en configuration pour les clients qui l'exigent (banque, secteur public).

---

## 12. Matrice rôles / permissions

Permissions granulaires, cumulables via rôles ET attribuables directement à un utilisateur ou un groupe sur un périmètre (entreprise / service / dossier / document).

| Permission | Super Admin | Admin entreprise | Responsable documentaire | Manager | Employé | Lecteur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Voir un document | — | ✅ | ✅ (périmètre) | ✅ (service) | ✅ (dossiers autorisés) | ✅ (lecture seule) |
| Télécharger | — | ✅ | ✅ | ✅ | ✅ | selon config |
| Créer/uploader | — | ✅ | ✅ | ✅ | ✅ | ❌ |
| Modifier métadonnées | — | ✅ | ✅ | ✅ (ses documents/service) | ✅ (ses documents) | ❌ |
| Créer une nouvelle version | — | ✅ | ✅ | ✅ | ✅ | ❌ |
| Restaurer une version | — | ✅ | ✅ | ✅ (droit explicite) | ❌ par défaut | ❌ |
| Déplacer/renommer | — | ✅ | ✅ | ✅ | ✅ (ses documents) | ❌ |
| Partager | — | ✅ | ✅ | ✅ | ✅ (droit explicite) | ❌ |
| Demander une signature électronique | — | ✅ | ✅ | ✅ | ❌ | ❌ |
| Soumettre en workflow | — | ✅ | ✅ | ✅ | ✅ | ❌ |
| Valider (approuver/rejeter) | — | ✅ | ✅ (selon étape) | ✅ (selon étape) | ❌ | ❌ |
| Archiver | — | ✅ | ✅ | ✅ (droit explicite) | ❌ | ❌ |
| Restaurer depuis corbeille | — | ✅ | ✅ | ✅ (ses documents) | ✅ (ses documents) | ❌ |
| Suppression définitive | — | ✅ | ❌ (sauf droit explicite) | ❌ | ❌ | ❌ |
| Gérer utilisateurs/rôles | — | ✅ | ❌ | ❌ | ❌ | ❌ |
| Configurer workflows/rétention | — | ✅ | ✅ | ❌ | ❌ | ❌ |
| Consulter le journal d'audit | — | ✅ | ✅ | ✅ (son périmètre) | ❌ | ❌ |
| Gérer les entreprises (tenants) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Principes** :
- Les permissions par défaut ci-dessus sont un point de départ (`is_system = true`) ; l'Administrateur entreprise peut créer des rôles personnalisés en combinant librement les permissions atomiques.
- Une permission peut être accordée/retirée à un niveau plus fin (dossier, document précis) — elle surcharge alors l'héritage du rôle pour ce périmètre uniquement.
- Principe du **moindre privilège** par défaut : un nouvel utilisateur créé sans rôle explicite n'a **aucun accès**.

---

## 13. Workflow documentaire

### 13.1 Modèle générique (configurable par type de document)

```
[Brouillon] --soumettre--> [Soumis] --(auto)--> [Étape 1 : Validation manager]
                                                       |-- approuve --> [Étape 2 : Validation direction]
                                                       |                      |-- approuve --> [Publié] --archiver--> [Archivé]
                                                       |                      |-- rejette  --> [Brouillon] (+notification auteur)
                                                       |-- rejette  --> [Brouillon] (+notification auteur)
                                                       |-- demande modification --> [Brouillon] (+commentaire obligatoire)
```

- Chaque `workflow_definitions` associe un type de document à une suite ordonnée de `workflow_steps`.
- Une étape référence soit un **rôle** (n'importe quel titulaire du rôle peut agir), soit une **personne nommée** (délégation possible en V2).
- MVP : workflow linéaire (étapes séquentielles). V2 : embranchements conditionnels (ex. "si montant > 10 000 €, ajouter une étape direction financière").

### 13.2 Traçabilité (obligatoire, non désactivable)

Chaque action de workflow crée une ligne `workflow_actions` immuable : utilisateur, date/heure, action (`approuve`/`rejete`/`demande_modification`/`commentaire`), commentaire. Ces lignes alimentent également le journal d'audit global.

### 13.3 Relation avec la signature électronique (section 13 du cahier des charges)

Le bouton "Approuver" en fin de workflow **n'est pas** une signature électronique au sens juridique. L'architecture prévoit un point d'extension `SignatureProvider` (interface) permettant, en V2, de déclencher une signature qualifiée via un prestataire tiers (ex. Yousign, DocuSign, Universign, ou une solution certifiée eIDAS/locale selon le pays cible) à l'étape finale du workflow, avec réception du certificat de preuve et rattachement au document. **Ne jamais présenter la validation applicative interne comme équivalente juridiquement** à une signature électronique qualifiée — le distinguo doit apparaître dans l'interface elle-même (ex. libellé "Approuver en interne" vs. futur "Signer électroniquement").

---

## 14. Politique d'archivage

### 14.1 Cycle de vie d'un document

```
Actif ──(publié, en usage courant)
  │
  │  déclenché manuellement OU automatiquement par le moteur de rétention
  ▼
Archivé ──(date_archivage, categorie, confidentialite, motif enregistrés)
  │
  │  à l'échéance de la durée de conservation définie par retention_policies
  ▼
Proposé pour destruction ──(nécessite une validation humaine explicite, JAMAIS automatique)
  │
  ▼
Détruit ──(purge physique + entrée audit_logs terminale, irréversible)
```

### 14.2 Moteur de politique de rétention

Table `retention_policies` : `document_type → duree_conservation_mois → action_a_expiration`.

Actions possibles à l'échéance (section 15 du cahier des charges) :
- **Conserver** : aucune action, la durée est simplement prolongée/réévaluée.
- **Proposer pour destruction** : crée une tâche pour le responsable documentaire, ne détruit rien tant que non validé.
- **Transférer vers une archive** : change le statut en `archive`, déplace potentiellement vers un disque de stockage froid (S3 classe archive).
- **Demander une validation avant destruction** : notifie un rôle désigné ; la destruction physique ne survient qu'après validation explicite tracée (utilisateur, date, motif).

> **Garde-fou non contournable** : le code n'implémente **aucun chemin** de suppression physique définitive sans passage par une validation humaine enregistrée. La tâche planifiée qui détecte les échéances ne fait que **proposer**, jamais détruire.

### 14.3 Exemples de politiques par défaut (à ajuster par le client et son conseil juridique)

| Type de document | Durée indicative | Remarque |
|---|---|---|
| Contrats commerciaux | 10 ans après fin du contrat | Durée à confirmer selon droit applicable (ex. prescription civile en France : 5 ans, mais pratique courante à 10 ans pour litiges commerciaux) |
| Factures | Selon obligations fiscales locales (ex. 6 à 10 ans en France) | **À vérifier avec un expert-comptable/juridique du pays cible avant paramétrage** |
| Documents RH (contrats de travail, paie) | Selon code du travail local (souvent 5 ans après le départ du salarié, davantage pour certains documents) | **À vérifier avec un juriste RH local — variable par pays et par nature de document** |
| Documents juridiques/statutaires | Durée de vie de la société + délai post-dissolution | Ne jamais fixer de durée automatique de destruction pour ces documents sans validation juridique explicite |

> Rappel transverse : ces durées sont **indicatives**, ne constituent pas un conseil juridique, et doivent être confirmées avec un professionnel du droit compétent dans le pays d'exploitation de chaque client avant toute activation de destruction automatique proposée.

### 14.4 Corbeille (distincte de l'archivage)

- Suppression "logique" (`is_trashed = true`) ≠ archivage (statut métier `archive`). Un document supprimé par erreur reste techniquement présent et restaurable.
- Rétention de la corbeille configurable (ex. purge automatique proposée après 30 jours — toujours avec confirmation admin, jamais purge silencieuse).
- Suppression définitive réservée aux rôles disposant explicitement de la permission `document.delete.permanent` (par défaut : Admin entreprise uniquement).
