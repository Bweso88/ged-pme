# YOSEFA — Documentation produit & architecture

> **Vous voulez juste installer et lancer le projet, en particulier si vous découvrez Laravel ?**
> Commencez par [00-installation-debutant.md](./00-installation-debutant.md). Les documents
> ci-dessous sont l'architecture et les décisions produit, pas un guide d'installation.

Ce dossier contient les 20 livrables demandés avant l'implémentation, regroupés en 6 documents thématiques :

| Fichier | Livrables couverts |
|---|---|
| [01-vision-produit.md](./01-vision-produit.md) | 1. Vision produit — 2. Personas — 3. Cas d'utilisation — 4. Fonctionnalités MVP/V2/V3 |
| [02-architecture-technique.md](./02-architecture-technique.md) | 5. Architecture technique — 6. Architecture multi-tenant — 9. Architecture API — 10. Architecture stockage — 17. Architecture de déploiement |
| [03-modele-donnees.md](./03-modele-donnees.md) | 7. Schéma de base de données — 8. Diagramme des relations |
| [04-securite-workflow-archivage.md](./04-securite-workflow-archivage.md) | 11. Modèle de sécurité — 12. Matrice rôles/permissions — 13. Workflow documentaire — 14. Politique d'archivage |
| [05-ecrans-parcours.md](./05-ecrans-parcours.md) | 15. Liste des écrans — 16. Parcours utilisateur |
| [06-sauvegarde-plan-dev-tests.md](./06-sauvegarde-plan-dev-tests.md) | 18. Stratégie de sauvegarde — 19. Plan de développement — 20. Plan de tests |

Le code applicatif (MVP Laravel) se trouve dans le reste du dossier `ged-pme/`.

## Avertissement juridique

Cette solution est une **GED + archivage fonctionnel**. Elle ne constitue pas automatiquement une solution d'**archivage électronique à valeur probante** (NF Z42-013/ISO 14641, eIDAS, ou équivalents locaux). Voir la section 1.3 de [01-vision-produit.md](./01-vision-produit.md) et la section 14 de [04-securite-workflow-archivage.md](./04-securite-workflow-archivage.md). Toute durée légale de conservation mentionnée dans cette documentation est indicative et doit être validée avec un juriste compétent dans le pays cible avant tout paramétrage contractuel.
