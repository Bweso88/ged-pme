# YOSEFA — Liste des écrans et parcours utilisateur

> Livrables couverts : 15. Liste des écrans — 16. Parcours utilisateur

## 15. Liste des écrans

### Authentification / compte
1. Connexion (email + mot de passe, lien "mot de passe oublié")
2. Réinitialisation de mot de passe
3. Mon profil (informations personnelles, changement de mot de passe)

### Cœur applicatif
4. **Dashboard** — indicateurs clés, graphiques, activité récente, tâches en attente
5. **Explorateur documentaire** — arborescence de dossiers + liste des documents (vue liste/grille), drag & drop d'upload
6. **Fiche document** — aperçu, métadonnées, versions, workflow, partages, commentaires, journal d'activité du document
7. **Comparateur de versions** — liste des versions, aperçu, restauration
8. **Recherche avancée** — filtres combinés + résultats + surlignage des extraits OCR correspondants
9. **Corbeille** — documents supprimés, restauration, suppression définitive (admin)
10. **Mes tâches / à valider** — file des documents en attente de décision de l'utilisateur connecté
11. **Notifications** — centre de notifications in-app
12. **Partages reçus** — documents partagés avec l'utilisateur, liens actifs qu'il a créés

### Module archivage
13. **Archives** — liste des documents archivés, filtres par catégorie/confidentialité/échéance
14. **Propositions de destruction** — file d'attente à valider par le responsable documentaire

### Administration (Admin entreprise)
15. Utilisateurs (liste, création, édition, suspension)
16. Rôles et permissions (matrice éditable)
17. Groupes d'utilisateurs
18. Services / structure organisationnelle
19. Structure documentaire (arborescence de dossiers par défaut)
20. Types de documents
21. Métadonnées configurables (par type de document)
22. Workflows (constructeur d'étapes)
23. Politiques de rétention / archivage
24. Paramètres généraux (taille max fichiers, extensions autorisées, durée de session, logo, couleurs — white label)
25. Journal d'audit (recherche/filtre/export)
26. Stockage & sauvegardes (état du quota, dernière sauvegarde)

### Super Administration (plateforme SaaS)
27. Liste des entreprises clientes (tenants)
28. Fiche entreprise (plan, statut, quota, admin initial)
29. Métriques globales de la plateforme (nombre de tenants, volumétrie agrégée)

### Accès public contrôlé
30. Page d'accès à un lien de partage (saisie du mot de passe si requis, expiration affichée)

## 16. Parcours utilisateur (principaux)

### Parcours A — Employé dépose et fait valider un contrat
1. Connexion → Dashboard
2. Explorateur documentaire → dossier "Achats" → glisser-déposer le PDF scanné
3. Formulaire de métadonnées (type = Contrat, référence, date, service) → validation
4. OCR lancé en tâche de fond → notification "Traitement terminé" une fois le texte extrait
5. Clic sur "Soumettre au workflow" → sélection du workflow "Contrat fournisseur"
6. Document passe en statut "Soumis" → notification au manager

### Parcours B — Manager valide un document
1. Connexion → notification "1 document à valider" → clic
2. Écran "Mes tâches / à valider" → ouverture de la fiche document
3. Consultation du document, des métadonnées, de l'historique
4. Action "Approuver" (+ commentaire optionnel) ou "Rejeter" (commentaire obligatoire) ou "Demander une modification"
5. Si approuvé et dernière étape → document passe en statut "Publié", auteur notifié

### Parcours C — Recherche d'un document ancien (avec OCR)
1. Barre de recherche → saisie "contrat fournisseur 2025"
2. Résultats combinant correspondances sur nom/métadonnées ET texte OCR, avec extrait surligné
3. Filtre additionnel par service = "Achats"
4. Ouverture du document → téléchargement (action journalisée dans l'audit)

### Parcours D — Responsable documentaire traite une échéance de conservation
1. Dashboard → widget "Documents arrivant à échéance"
2. Écran "Propositions de destruction" → document listé avec sa politique de rétention appliquée
3. Consultation du document et de son historique
4. Décision : "Valider la destruction" (confirmation forte, motif obligatoire) ou "Prolonger la conservation"
5. Action tracée dans l'audit, document détruit uniquement après validation explicite

### Parcours E — Administrateur entreprise partage un document avec un prestataire externe
1. Fiche document → "Partager" → type "Lien"
2. Définition de l'expiration (ex. 7 jours), mot de passe optionnel
3. Lien généré et copié → envoyé au prestataire par email externe à l'outil
4. Prestataire ouvre le lien → saisit le mot de passe → consulte/télécharge selon droits accordés
5. Administrateur peut révoquer le lien à tout moment depuis la fiche document

### Parcours F — Super Admin onboarde une nouvelle entreprise cliente
1. Connexion à l'espace Super Admin
2. "Nouvelle entreprise" → nom, plan, quota de stockage
3. Création automatique du compte Administrateur entreprise + email d'invitation
4. L'administrateur entreprise se connecte, configure sa structure documentaire, ses utilisateurs, son logo
