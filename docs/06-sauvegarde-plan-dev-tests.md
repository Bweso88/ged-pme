# YOSEFA — Stratégie de sauvegarde, plan de développement, plan de tests

> Livrables couverts : 18. Stratégie de sauvegarde — 19. Plan de développement — 20. Plan de tests

---

## 18. Stratégie de sauvegarde

Une simple copie du serveur (snapshot VM) **n'est pas** considérée comme une stratégie de sauvegarde complète : elle ne garantit ni la granularité de restauration, ni la vérification d'intégrité, ni la conservation hors-site.

### 18.1 Composants sauvegardés séparément

| Composant | Méthode | Fréquence | Rétention |
|---|---|---|---|
| Base de données PostgreSQL | `pg_dump` chiffré (format custom, restaurable table par table) | Quotidienne (+ WAL archiving en option pour PITR) | 30 jours glissants + 1 sauvegarde mensuelle sur 12 mois |
| Fichiers documentaires | Sauvegarde incrémentale (rsync/borg/restic) vers un stockage distinct (idéalement hors-site ou autre zone S3) | Quotidienne | Alignée sur la rétention DB |
| Configuration (`.env`, docker-compose, config applicative) | Versionnée dans un dépôt privé séparé (jamais de secret en clair) | À chaque changement | Historique Git complet |
| Logs applicatifs et journal d'audit | Export périodique vers un stockage froid, en plus de la table `audit_logs` (append-only) | Hebdomadaire | Selon obligations légales du client (souvent plusieurs années) |

### 18.2 Règle 3-2-1

Au minimum 3 copies des données, sur 2 supports différents, dont 1 hors-site (autre datacenter/région que la production). Pour l'offre PME à 3 000 €, cela se traduit concrètement par : disque local (prod) + serveur de sauvegarde distinct chez le même hébergeur + copie hors-site chez un second prestataire (ex. stockage objet low-cost) pour les clients qui souscrivent l'option hébergement/sauvegarde récurrente.

### 18.3 Vérification d'intégrité

- Job planifié mensuel : restauration automatique de la dernière sauvegarde dans un environnement isolé + vérification (checksum, comptage de lignes, ouverture d'un échantillon de documents) + rapport envoyé à l'équipe support.
- Une sauvegarde jamais testée est considérée comme non fiable par principe.

### 18.4 Procédure de restauration documentée

Runbook versionné (hors du présent document produit, dans la documentation d'exploitation) couvrant : restauration complète (sinistre serveur), restauration ponctuelle (un document supprimé par erreur avant la fenêtre de rétention corbeille), restauration à un point dans le temps (PITR) pour les clients avec cette option.

---

## 19. Plan de développement

### 19.1 Découpage en jalons (alignés sur les tâches de ce dépôt)

| Jalon | Contenu | Sortie attendue |
|---|---|---|
| J1 — Socle | Scaffold Laravel, multi-tenant (migrations, Global Scope, RLS), auth, RBAC (spatie/laravel-permission), seeders rôles/permissions | Connexion fonctionnelle, isolation tenant vérifiée par tests |
| J2 — Cœur documentaire | Dossiers, upload, versioning, métadonnées configurables, policies | Upload/téléchargement/versioning opérationnels |
| J3 — Recherche & OCR | Recherche filtrée + full-text, job OCR Tesseract asynchrone | Un document scanné est retrouvable par son contenu |
| J4 — Workflow & validation | Moteur de workflow configurable, actions de validation tracées | Un document suit un circuit de validation de bout en bout |
| J5 — Archivage & rétention & corbeille | Statuts, politiques de rétention, tâche planifiée d'échéance, corbeille | Un document peut être archivé, une échéance proposée, jamais détruite sans validation |
| J6 — Partage & notifications | Partage interne/lien/mot de passe/expiration, notifications in-app + email | Partage sécurisé fonctionnel de bout en bout |
| J7 — Audit & dashboard & administration | Journal d'audit, dashboard, écrans admin complets | Administrateur entreprise autonome sur la configuration |
| J8 — Durcissement & tests | Revue sécurité, tests automatisés, corrections | Suite de tests verte, checklist sécurité validée |
| J9 — Packaging déploiement | Docker Compose, documentation d'installation, script de seed initial client | Déploiement chez un premier client pilote en < 1 jour |

### 19.2 Méthode

- Développement itératif par jalon, chaque jalon se termine par une démonstration fonctionnelle et une exécution de la suite de tests.
- Convention de code : PSR-12, typage strict PHP (`declare(strict_types=1)`), Form Requests pour toute validation d'entrée, Policies pour toute autorisation.
- Revue systématique des points de sécurité transverses (upload, téléchargement, isolation tenant) à chaque jalon touchant ces zones.

---

## 20. Plan de tests

### 20.1 Niveaux de test

| Niveau | Outil | Portée |
|---|---|---|
| Unitaire | PHPUnit/Pest | Services métier purs (calcul d'échéance de rétention, résolution de permissions, moteur de workflow) |
| Fonctionnel (feature) | PHPUnit/Pest + `RefreshDatabase` | Flux HTTP complets : upload, téléchargement, workflow, partage, corbeille, audit |
| Sécurité / isolation tenant | Tests dédiés | Vérifier qu'un utilisateur de l'entreprise A ne peut **jamais** lire/modifier une ressource de l'entreprise B (accès direct par ID compris) |
| Non-régression | Suite complète en CI | À chaque push sur la branche de développement |

### 20.2 Scénarios de test critiques (priorité maximale)

1. Un utilisateur non authentifié ne peut accéder à aucune route documentaire protégée.
2. Un utilisateur de l'entreprise A qui force l'URL d'un document de l'entreprise B reçoit un 404 (pas un 403 qui confirmerait l'existence).
3. Un upload avec une extension interdite ou un contenu ne correspondant pas au type MIME déclaré est rejeté.
4. Un document ne peut jamais être écrasé silencieusement : chaque nouvelle version crée une ligne distincte.
5. Un document ne peut être détruit définitivement sans un enregistrement de validation explicite (utilisateur + motif + date).
6. Un lien de partage sans date d'expiration ne peut pas être créé (contrainte applicative + contrainte SQL).
7. Chaque action sensible (connexion, téléchargement, suppression, partage, validation, archivage) génère bien une ligne dans `audit_logs`.
8. Un utilisateur sans la permission requise ne peut pas valider une étape de workflow, même en appelant directement la route API.
9. La suppression "corbeille" est réversible ; la restauration replace le document dans son dossier d'origine avec ses métadonnées intactes.
10. Le moteur de rétention détecte correctement une échéance et crée une proposition, sans jamais déclencher de destruction automatique.

### 20.3 Outils complémentaires

- **Analyse statique** : PHPStan (niveau élevé) intégré au pipeline.
- **Audit de sécurité applicative** : checklist OWASP Top 10 revue avant chaque déploiement client.
- **Test de charge** (avant commercialisation SaaS mutualisée) : simulation de plusieurs entreprises actives simultanément pour valider l'isolation sous charge et le comportement des files de jobs (OCR).
