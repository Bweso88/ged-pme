# YOSEFA — Vision produit, personas, cas d'usage et périmètre fonctionnel

> Livrables couverts : 1. Vision produit — 2. Personas — 3. Cas d'utilisation — 4. Fonctionnalités MVP/V2/V3

---

## 1. Vision produit

### 1.1 Positionnement

**YOSEFA** est une plateforme complète de gestion et d'archivage des documents de l'entreprise, pensée pour les PME, administrations, cabinets et associations qui n'ont ni le budget ni les équipes IT des grands comptes, mais qui ont les mêmes besoins fonctionnels de fond : centraliser, sécuriser, retrouver, faire circuler et conserver leurs documents dans le temps.

Ce n'est **pas** un simple espace de stockage de fichiers (type Google Drive/OneDrive). La différence tient à quatre piliers :

1. **Structure et gouvernance documentaire** : classement configurable, métadonnées, types de documents, cycle de vie.
2. **Circulation contrôlée** : workflows de validation, traçabilité des décisions.
3. **Archivage et conservation** : statuts, durées légales/contractuelles, politiques de rétention, corbeille contrôlée.
4. **Sécurité et auditabilité** : RBAC fin, journal d'audit complet, partages maîtrisés dans le temps.

### 1.2 Proposition de valeur

| Douleur PME | Réponse YOSEFA |
|---|---|
| Documents dispersés (email, disque partagé, papier) | Espace documentaire centralisé, structuré par service |
| "Où est la dernière version du contrat ?" | Versioning strict + recherche avancée + OCR |
| Aucune trace de qui a validé quoi | Workflow + validation électronique horodatée |
| Durées de conservation non maîtrisées (risque juridique) | Moteur de politiques de rétention configurable |
| Suppressions accidentelles irréversibles | Corbeille + restauration + suppression définitive contrôlée |
| Pas de preuve en cas de contrôle/audit | Journal d'audit exhaustif |
| Solutions du marché trop chères / trop complexes à déployer | Déploiement en quelques jours, tarif d'entrée ~3 000 € |

### 1.3 Avertissement juridique transverse (à rappeler dans toute communication commerciale)

> La solution fournit une **GED + archivage fonctionnel** robuste (intégrité applicative, traçabilité, gestion du cycle de vie). Elle ne constitue **pas automatiquement** une solution d'**archivage électronique à valeur probante** au sens réglementaire (ex. NF Z42-013 / ISO 14641 en France, eIDAS pour la signature électronique en UE, ou équivalents locaux hors UE). La valeur probante dépend de mécanismes additionnels (scellement, horodatage qualifié, coffre-fort numérique certifié, procédure documentée) à évaluer avec le client selon son pays et son secteur. Ceci doit figurer explicitement dans les CGV et la documentation contractuelle.

---

## 2. Personas

### 2.1 Super Administrateur (éditeur SaaS)
- **Qui** : l'éditeur de YOSEFA (vous), gère la plateforme multi-tenant.
- **Besoins** : créer/suspendre des entreprises clientes, superviser l'usage (stockage, utilisateurs), gérer la facturation, supporter les clients.
- **Ne touche jamais** aux documents métier des entreprises clientes.

### 2.2 Administrateur de l'entreprise
- **Qui** : dirigeant, RAF, DSI d'une PME cliente.
- **Besoins** : configurer la structure documentaire, les utilisateurs, les droits, les workflows, la charte graphique (white label), les politiques d'archivage.

### 2.3 Responsable documentaire
- **Qui** : responsable qualité, RH, juridique, archiviste interne.
- **Besoins** : définir les métadonnées, catégories, règles de conservation ; superviser la corbeille et les archives ; produire des rapports d'audit.

### 2.4 Manager
- **Qui** : chef de service (achats, RH, commercial…).
- **Besoins** : valider/rejeter les documents de son périmètre, suivre les échéances de son service, déléguer.

### 2.5 Employé
- **Qui** : collaborateur opérationnel.
- **Besoins** : déposer, classer, rechercher, partager ses documents de travail ; soumettre à validation.

### 2.6 Lecteur / Consultation uniquement
- **Qui** : partenaire externe, commissaire aux comptes, auditeur, stagiaire.
- **Besoins** : consulter et éventuellement télécharger un périmètre restreint, sans droit de modification.

---

## 3. Cas d'utilisation principaux (use cases)

| # | Acteur | Cas d'usage | Résultat attendu |
|---|---|---|---|
| UC-01 | Employé | Déposer un contrat scanné dans "Achats" | Document indexé, OCR lancé, métadonnées saisies |
| UC-02 | Employé | Rechercher "contrat fournisseur 2025" | Résultats incluant le texte extrait par OCR |
| UC-03 | Manager | Valider une note de frais | Statut mis à jour, historique de validation enregistré |
| UC-04 | Manager | Rejeter un document avec commentaire | Auteur notifié, document renvoyé en brouillon |
| UC-05 | Responsable documentaire | Définir la politique "Contrats = 10 ans" | Règle appliquée automatiquement aux nouveaux documents du type |
| UC-06 | Système (tâche planifiée) | Détecter un document arrivant à échéance de conservation | Notification + tâche "à traiter" au responsable |
| UC-07 | Administrateur entreprise | Créer un service "Juridique" avec droits dédiés | Nouveau nœud dans l'arborescence + permissions héritées |
| UC-08 | Employé | Partager un document via lien temporaire avec mot de passe | Lien valide 7 jours, révocable, tracé dans l'audit |
| UC-09 | Administrateur entreprise | Restaurer un document supprimé par erreur | Document restauré à son emplacement d'origine, action tracée |
| UC-10 | Super Admin | Créer une nouvelle entreprise cliente (onboarding SaaS) | Tenant isolé créé avec admin initial |
| UC-11 | Responsable documentaire | Consulter le journal d'audit d'un document sensible | Historique complet des accès/actions |
| UC-12 | Employé mobile | Consulter et valider un document depuis son téléphone | Interface responsive fonctionnelle |
| UC-13 | Manager | Restaurer une ancienne version d'un contrat | Nouvelle version créée à partir de l'ancienne, historique préservé |
| UC-14 | Administrateur entreprise | Personnaliser logo/couleurs (white label) | Interface reflète l'identité du client |

---

## 4. Fonctionnalités par version

### 4.1 MVP (version commercialisable, ~3 000 €)

Périmètre strictement aligné sur la section 32 du cahier des charges :

- Authentification sécurisée, gestion des utilisateurs et rôles (RBAC)
- Multi-entreprise (isolation des données), configuration de base (logo, nom)
- Arborescence de dossiers configurable
- Documents : upload (simple + glisser-déposer + multi-fichiers), téléchargement, renommage, déplacement, suppression logique
- Métadonnées configurables par type de document
- Recherche (filtres + recherche texte sur nom/métadonnées ; recherche plein texte si OCR disponible)
- OCR (Tesseract, asynchrone via file d'attente) sur PDF/images
- Versioning complet (historique, auteur, restauration)
- Partage (interne, lien temporaire avec expiration, mot de passe optionnel, révocation)
- Workflow simple configurable (états + transitions, validation manager/direction)
- Validation électronique (approuver/rejeter/demander modification + commentaire, horodatage)
- Archivage (statuts actif/archivé, date d'archivage, durée de conservation, catégorie, confidentialité, motif)
- Politique de conservation basique (type → durée → action, avec validation humaine obligatoire avant destruction)
- Corbeille + restauration + suppression définitive réservée aux admins
- Journal d'audit complet
- Notifications (in-app + email)
- Dashboard avec indicateurs clés
- Administration (utilisateurs, rôles, services, catégories, types de documents, métadonnées, workflows, politiques d'archivage, paramètres)
- Interface responsive (mobile web)

### 4.2 V2 (post-lancement, montée en gamme)

- Signature électronique : intégration d'un prestataire tiers (ex. Yousign, DocuSign, Universign) via interface d'intégration déjà prévue en V1
- Numérisation avancée : intégration de scanners réseau / dossiers de dépôt surveillés
- Groupes d'utilisateurs et délégations de validation
- Rapports et exports d'audit (PDF/CSV) pour contrôle réglementaire
- Recherche plein texte avancée (Meilisearch/Elasticsearch) à grande échelle
- White label complet (sous-domaine dédié, thème avancé)
- API publique documentée (intégrations tierces : ERP, paie, CRM)
- Application mobile native (Flutter)
- Sauvegardes et restauration self-service pour les administrateurs entreprise

### 4.3 V3 (différenciation, IA)

- Classification automatique des documents à l'upload
- Extraction automatique de métadonnées (dates, montants, parties) depuis l'OCR
- Résumé automatique de documents longs
- Recherche sémantique (embeddings) en complément de la recherche par mots-clés
- Assistant documentaire conversationnel ("Trouve-moi les contrats fournisseurs expirant dans 90 jours")
- Détection de documents similaires/doublons
- Suggestion automatique de classement (dossier, type, métadonnées)

> Principe directeur : **l'IA n'est jamais un prérequis du MVP**. L'architecture (métadonnées structurées, texte OCR indexé, événements d'audit) est conçue pour rendre ces briques IA ajoutables sans refonte.
