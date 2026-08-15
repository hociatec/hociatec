# Dépendances intermodules autorisées

## Règle générale

Un module reste propriétaire de ses entités, invariants, handlers et workflows métier. Un autre module ne peut pas réimplémenter ces règles : il consomme un port `Application/Port`, un handler/workflow applicatif public, une projection, un identifiant ou un snapshot.

## Matrice autorisée

| Module appelant | Modules autorisés | Usage autorisé |
| --- | --- | --- |
| `Admin` | Tous les modules métier | Composition d'écrans, projections, commandes administratives et exports. Les règles métier restent dans le module propriétaire. |
| `Order` | `Cart`, `Catalog`, `Promotion`, `Voucher`, `User`, `Notification`, `Outbox` | Checkout, snapshot de commande/facture, paiement, notifications et historique. |
| `Cart` | `Catalog`, `Promotion`, `Voucher`, `User` | Construction du panier courant, calcul promotionnel et rattachement client. |
| `Quote` | `Catalog`, `User`, `Notification` | Devis client, prestations chiffrées et notification. |
| `TradeIn` | `Catalog`, `User`, `Voucher`, `Notification` | Demande de reprise, snapshot produit, bon d'achat et suivi client. |
| `Marketing` | `User`, `Order`, `Notification` | Segmentation, contexte destinataire et diffusion. |
| `Notification` | `User` | Préférences de communication et destinataires. |
| `Promotion`, `Voucher`, `Loyalty`, `Rating`, `Favorite` | `User`, `Catalog`, `Order` selon le cas d'usage | Eligibilité, rattachement client, produit ou commande. |
| `Support`, `Audit`, `Training`, `Appointment`, `BetaTest`, `News` | `User`, `Notification` et leurs modules de référence métier | Parcours client, commentaires, réservations, suivis et notifications. |

## Règles `User`

Utiliser l'entité `User` uniquement quand le module a besoin des préférences, rôles, email courant ou relations Doctrine déjà persistées. Utiliser un identifiant, un email normalisé ou un snapshot dès que le besoin est historique, documentaire ou seulement référentiel.

Les factures, commandes, devis, demandes de reprise, campagnes marketing et exports doivent conserver des snapshots pour les informations présentées au client ou à l'administration. La relation vers `User` sert alors à l'accès, au rattachement et aux préférences, pas à reconstruire a posteriori le contenu métier historisé.

## Module `Admin`

`Admin` peut dépendre de ports et workflows des autres modules pour composer des opérations transverses, mais ne doit contenir que :

- contrôleurs et DTO d'administration ;
- projections, formatters et exports admin ;
- orchestration d'opérations administratives ;
- interfaces de persistence propres aux opérations admin.

Les invariants comme les statuts commande, remboursements, stock, support, devis et notifications restent dans les modules propriétaires.

## Revue des `OperationsService`

- `SupportOperationsService` couvre un workflow SAV cohérent : créer, mettre à jour et répondre à une demande support.
- `RefundOperationsService` couvre un workflow remboursement cohérent : créer, suivre et déclencher le remboursement Stripe.
- `FulfillmentOperationsService` couvre un workflow livraison cohérent : file d'expédition et marquage expédié.
- `StockOperationsService` couvre un workflow stock cohérent : mouvements de stock et seuil bas.

Ces classes restent acceptables tant qu'elles orchestrent ces workflows uniques. Toute nouvelle opération métier lourde doit être déplacée dans le module propriétaire puis appelée depuis `Admin`.

## Revue des entités volumineuses

- `User` agrège identité, sécurité, préférences et administration via traits dédiés ; éviter d'y ajouter des règles de modules clients.
- `EmailCampaign` et ses destinataires restent centrés sur campagne, planification et état d'envoi.
- `ServiceOffering` reste centré sur les prestations de devis et leur publication.
- `Voucher` reste centré sur éligibilité, cycle d'activation et rattachement bénéficiaire.
- `Product` est découpé par objets embarqués et traits pour pricing, inventaire, publication, galerie, caractéristiques et avis.

Si l'une de ces entités dépasse son sous-domaine actuel, extraire d'abord un objet de valeur ou un service de domaine dans le module propriétaire.

## Exceptions techniques contrôlées

Les exceptions suivantes existent encore et doivent rester limitées, explicites et revues régulièrement :

- le bridge Symfony Security (`SymfonySecurityUser`, `EmailUserProvider`, fusion de panier après login) conserve une adaptation infrastructure couplée à `Auth` et `User`.

Toute nouvelle dépendance vers `UI` ou `Infrastructure` d’un autre module doit être refusée hors de cette liste.

## Baseline intermodule gelée

Le graphe top-level ci-dessous est la baseline actuellement acceptée par les contrôles d’architecture. Toute nouvelle paire `ModuleA -> ModuleB` doit être justifiée puis ajoutée ici dans le même commit que le changement de code. Si une paire disparaît, cette liste doit être nettoyée.

- `Admin -> Appointment`
- `Admin -> Audit`
- `Admin -> BetaTest`
- `Admin -> Catalog`
- `Admin -> Marketing`
- `Admin -> News`
- `Admin -> Notification`
- `Admin -> Order`
- `Admin -> Promotion`
- `Admin -> Quote`
- `Admin -> Rental`
- `Admin -> Service`
- `Admin -> Support`
- `Admin -> TradeIn`
- `Admin -> User`
- `Admin -> Voucher`
- `Appointment -> User`
- `Audit -> User`
- `Auth -> Appointment`
- `Auth -> BetaTest`
- `Auth -> Catalog`
- `Auth -> Marketing`
- `Auth -> Order`
- `Auth -> Outbox`
- `Auth -> Rating`
- `Auth -> User`
- `BetaTest -> Notification`
- `BetaTest -> User`
- `Cart -> Auth`
- `Cart -> Catalog`
- `Cart -> Order`
- `Cart -> Promotion`
- `Cart -> User`
- `Cart -> Voucher`
- `Catalog -> Marketing`
- `Catalog -> Rating`
- `Catalog -> User`
- `Comment -> Rating`
- `Contact -> Marketing`
- `Favorite -> Catalog`
- `Favorite -> News`
- `Favorite -> Service`
- `Favorite -> User`
- `Loyalty -> Order`
- `Loyalty -> User`
- `Loyalty -> Voucher`
- `Marketing -> Notification`
- `Marketing -> Order`
- `Marketing -> Outbox`
- `Marketing -> Rating`
- `Marketing -> User`
- `News -> Marketing`
- `News -> Notification`
- `News -> User`
- `Notification -> Appointment`
- `Notification -> Audit`
- `Notification -> Rating`
- `Notification -> Training`
- `Notification -> User`
- `Notification -> Voucher`
- `Order -> Cart`
- `Order -> Catalog`
- `Order -> Marketing`
- `Order -> Notification`
- `Order -> Promotion`
- `Order -> Quote`
- `Order -> Rating`
- `Order -> Rental`
- `Order -> Training`
- `Order -> User`
- `Order -> Voucher`
- `Promotion -> Cart`
- `Promotion -> Order`
- `Promotion -> User`
- `Quote -> Catalog`
- `Quote -> Marketing`
- `Quote -> Notification`
- `Quote -> Order`
- `Quote -> Outbox`
- `Quote -> Service`
- `Quote -> User`
- `Rating -> Catalog`
- `Rating -> Comment`
- `Rating -> Order`
- `Rating -> User`
- `Rental -> Order`
- `Rental -> User`
- `Support -> Admin`
- `Support -> Order`
- `Support -> User`
- `System -> Outbox`
- `TradeIn -> Catalog`
- `TradeIn -> Marketing`
- `TradeIn -> Notification`
- `TradeIn -> User`
- `TradeIn -> Voucher`
- `Training -> Order`
- `Training -> User`
- `User -> Auth`
- `User -> BetaTest`
- `User -> Marketing`
- `User -> Notification`
- `User -> Order`
- `User -> Outbox`
- `User -> Quote`
- `User -> Support`
- `User -> TradeIn`
- `Voucher -> Cart`
- `Voucher -> Marketing`
- `Voucher -> Notification`
- `Voucher -> User`
