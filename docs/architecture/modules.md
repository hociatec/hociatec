# Modules Backend

## Responsabilités

- `Admin` orchestre les vues et commandes d’administration. Il ne possède pas les règles métier des autres modules.
- `Auth` gère authentification, sessions, refresh tokens et cookies HTTP via l’UI/infrastructure.
- `User` possède les comptes, profils et adresses.
- `Catalog` possède produits, catégories, marques, stock et projections catalogue.
- `Cart` possède le panier et ses promotions appliquées.
- `Order` possède commandes, paiement, factures et remboursements.
- `Quote` possède devis et prestations.
- `TradeIn` possède les demandes de reprise, offres, statuts et documents privés associés.
- `Voucher`, `Promotion`, `Loyalty`, `Rating`, `Favorite`, `Notification`, `Marketing`, `Audit`, `Training`, `Appointment`, `Support`, `BetaTest`, `News`, `Contact` possèdent chacun leur périmètre métier explicite.

## Dépendances Autorisées

- Les modules lisent un autre module par ses ports `Application/Port`.
- Les écritures intermodules passent par un handler/workflow applicatif ou une transaction explicite.
- `Admin` peut composer plusieurs modules pour une opération d’administration, mais ne duplique pas leurs invariants.
- `Notification` et `Outbox` transportent des événements ou messages, pas des décisions métier.
- Les associations Doctrine entre modules doivent être justifiées par une navigation métier fréquente ; sinon préférer des identifiants ou snapshots.

## Données

Chaque module reste propriétaire de ses entités principales. Les snapshots intermodules sont autorisés pour historiser une décision, une facture ou un document, mais doivent prévoir anonymisation ou suppression logique lorsque des données personnelles sont conservées.

