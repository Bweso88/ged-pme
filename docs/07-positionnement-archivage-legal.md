# YOSEFA — Ce que c'est (et ce que ce n'est pas) en matière d'archivage légal

> Document de positionnement, à usage commercial et contractuel. Objectif : éviter toute ambiguïté
> avec un prospect ou un client sur la nature exacte de la solution en matière d'archivage.

---

## 1. En une phrase

**YOSEFA est une GED (Gestion Électronique des Documents) avec des fonctions sérieuses de
gouvernance documentaire et de rétention. Ce n'est pas un Système d'Archivage Électronique (SAE)
certifié à valeur probante.**

Cette distinction n'est pas cosmétique : elle détermine si un document conservé dans YOSEFA peut,
seul, faire preuve devant un tribunal ou satisfaire une obligation réglementaire sectorielle stricte.

---

## 2. Ce que YOSEFA fait, et fait bien

| Fonction | Statut dans YOSEFA |
|---|---|
| Cycle de vie du document (actif → archivé → proposé destruction → détruit) | ✅ Implémenté, aucune destruction automatique possible |
| Politiques de rétention par type de document | ✅ Configurable (durée, action à échéance) |
| Validation humaine obligatoire avant toute destruction définitive | ✅ Utilisateur + motif + date enregistrés |
| Journal d'audit sur les actions sensibles (accès, partage, validation, destruction) | ✅ Présent |
| Isolation stricte entre entreprises clientes (multi-tenant) | ✅ Double couche (applicative + base de données) |
| Empreinte d'intégrité du fichier à l'upload (SHA-256) | ✅ Calculée et stockée |
| Versioning, workflow de validation, permissions granulaires | ✅ Complet |

Pour la très grande majorité des PME — facturation, contrats commerciaux courants, dossiers RH,
correspondance administrative — ce niveau de rigueur est largement suffisant et déjà au-dessus de
la moyenne des outils comparables sur ce segment de prix.

---

## 3. Ce que YOSEFA ne fait pas (et qu'un vrai SAE fait)

| Exigence d'un SAE certifié | Statut dans YOSEFA |
|---|---|
| Certification NF Z42-013 / ISO 14641 (SAE), ou équivalent eIDAS pour la valeur probante | ❌ Non certifié |
| Vérification d'intégrité **périodique** (recalcul et comparaison régulière des empreintes, détection de corruption silencieuse) | ❌ Empreinte calculée une seule fois, à l'upload |
| Stockage WORM (Write Once Read Many) garanti au niveau du support physique | ❌ Protection applicative uniquement, pas de garantie médium |
| Journal d'audit techniquement inaltérable (permissions base verrouillées, chaînage cryptographique) | ❌ Append-only par convention applicative, non par contrainte technique |
| Stratégie de pérennité des formats (conversion PDF/A, migration à horizon 10-30 ans) | ❌ Absent |
| Plan de classement archivistique normé (type ISAD(G), MoReq2010) avec cotation | ❌ Arborescence de dossiers GED, pas un plan de classement d'archives |
| Gel juridique ("litigation hold") suspendant les échéances de rétention en cas de contentieux | ❌ Absent |
| Bordereau de versement / élimination structuré et opposable | ❌ Validation tracée, mais pas de bordereau normé exportable |

---

## 4. Quand YOSEFA suffit, quand il ne suffit pas

**YOSEFA seul est adapté si :**
- Vous êtes une PME, un cabinet ou une association sans obligation d'archivage légal renforcée.
- Le besoin est avant tout organisationnel : retrouver un document, tracer qui a fait quoi, éviter
  la perte de fichiers, gérer un circuit de validation.
- La durée de conservation vise la bonne gestion interne (délais de prescription courants), pas une
  obligation réglementaire sectorielle stricte.

**YOSEFA seul ne suffit pas si :**
- Vous êtes soumis à une obligation sectorielle stricte (banque, assurance, secteur public,
  professions réglementées avec obligations d'archivage probant).
- Le contentieux est fréquent et la force probante du document est elle-même l'enjeu (pas
  seulement sa disponibilité).
- Une autorité de tutelle ou un commissaire aux comptes exige explicitement une solution certifiée.

Dans ce second cas, la recommandation est un **archivage à deux niveaux** : YOSEFA comme couche de
gestion documentaire quotidienne (recherche, workflow, accès), couplé en aval à un **tiers-archiveur
certifié** (ex. Locarchives, Coffreo, Cecurity, ou équivalent local) pour les documents nécessitant
une valeur probante certifiée sur le long terme.

---

## 5. Position commerciale recommandée

Ne jamais présenter YOSEFA comme "solution d'archivage légal" sans nuance auprès d'un prospect ayant
une obligation réglementaire stricte. La formulation correcte, à utiliser systématiquement :

> "YOSEFA est une solution de gestion électronique de documents avec gouvernance documentaire et
> politiques de rétention configurables. Pour les documents nécessitant une valeur probante
> certifiée (NF Z42-013 / ISO 14641 / eIDAS), nous recommandons un archivage complémentaire chez un
> tiers-archiveur certifié — que nous pouvons vous aider à sélectionner et intégrer."

Cette transparence est un argument de confiance, pas une faiblesse commerciale : elle démontre une
maîtrise réelle du sujet face à un client qui, lui, connaît la différence.

---

*Voir aussi : section 1.3 de [01-vision-produit.md](./01-vision-produit.md) et section 14 de
[04-securite-workflow-archivage.md](./04-securite-workflow-archivage.md).*
