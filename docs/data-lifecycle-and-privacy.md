# Cycle De Vie Des Données Et RGPD

## Suppression, conservation et anonymisation

Chaque donnée doit avoir une stratégie explicite :

- `User` : suppression bloquée si des commandes actives existent ; les sessions refresh sont révoquées avant suppression ;
- `Order`, `Quote`, `TradeIn`, `Audit`, `Support`, `Marketing` : conservation métier via snapshots et journaux, avec anonymisation ciblée lorsqu’une donnée personnelle n’est plus nécessaire au support, à la comptabilité ou à la preuve ;
- documents privés `TradeIn` : conservation limitée, purge planifiée, stockage privé ;
- `Outbox`, notifications techniques, métriques applicatives : purge ou rotation selon leur utilité opérationnelle.

Une suppression physique ne doit pas casser un historique légal ou comptable. Quand l’historique doit survivre, anonymiser les champs personnels plutôt que supprimer l’enregistrement métier.

## Matrice de décision actuelle

| Donnée / agrégat | Décision actuelle | Règle |
| --- | --- | --- |
| `User` | suppression physique conditionnelle | seulement si aucune commande active ; révocation des refresh tokens avant suppression ; l’historique métier doit survivre via snapshots puis anonymisation ciblée |
| `ShippingAddress` | suppression physique | donnée opérationnelle rattachée au compte courant, sans conservation autonome |
| `Order` | conservation | jamais supprimée en routine ; support comptable, paiement, audit et preuve |
| `Quote` | conservation métier | suppression physique seulement pour brouillons/simulations explicitement supprimés ; les devis utilisés comme historique doivent être conservés |
| `TradeInRequest` | conservation métier par défaut | pas de suppression massive automatique ; documents privés purgés séparément selon la rétention |
| documents privés `TradeIn` | purge physique planifiée | suppression du fichier, pas de l’historique métier principal |
| `NewsArticle`, `NewsComment` | suppression physique éditoriale | contenu CMS administrable, sans exigence de conservation légale métier |
| `BetaCampaign`, `BetaTesterProfile`, `BugReport` | suppression physique produit | données de programme beta / QA, hors historique comptable ; les pièces jointes doivent être supprimées avec le ticket |
| `EmailTemplate` | suppression physique | artefact d’administration remplaçable, sans valeur historique autonome |
| `Brand`, `Category`, `Product`, `ServiceOffering` | suppression physique sous garde métier | refus si la suppression casse encore un usage métier actif ou un rattachement requis |
| `Outbox`, métriques, caches techniques | purge / rotation | suppression technique autorisée selon la durée de rétention opérationnelle |

Cette matrice décrit la décision actuelle du produit. Toute évolution légale ou métier doit d’abord mettre à jour cette matrice, puis le workflow de suppression correspondant.

## Droit d’accès / export

Si un export de données personnelles est exposé au produit, il doit :

- reposer sur les snapshots et projections métier stables ;
- borner le volume par pagination ou streaming ;
- filtrer les données privées d’autres utilisateurs ;
- tracer explicitement les champs exportés.

L’implémentation courante expose un export JSON authentifié du compte (`/api/auth/profile/export`) construit par un provider dédié. Le payload reste borné au périmètre compte + commandes + demandes de reprise + devis rattachés à l’utilisateur.

## Mise en oeuvre actuelle

- `User` : suppression physique seulement sans historique métier ; sinon anonymisation du compte, révocation préalable des refresh tokens et conservation des références métier.
- `Order` : les commandes anciennes restent conservées mais leurs snapshots de facturation/livraison sont anonymisés lors de la suppression de compte.
- `TradeInRequest` : conservation métier avec anonymisation du nom, email, téléphone, numéro de série et description libre quand le compte source est supprimé.
- `Quote` : conservation du devis avec anonymisation des champs client lorsqu’il sert d’historique commercial.

## Timezone

Les instants persistés doivent rester cohérents en UTC. Les APIs et documents sortants utilisent `DATE_ATOM` ou un format ISO 8601 explicite ; les conversions locales ne doivent apparaître qu’en entrée/sortie.
