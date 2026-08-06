# Règles Domaine

## TradeIn

- Les montants de reprise ne sont jamais corrigés silencieusement.
- Les montants négatifs sont rejetés.
- L’estimation maximum ne peut pas être inférieure au minimum.
- Les transitions de statut passent par les workflows/policies dédiés.
- Les documents RIB et justificatifs sont privés et lus via un port.

## Catalogue Et Commandes

- Le prix, la TVA et les quantités doivent rester cohérents avant persistance.
- Les snapshots de commande/facture conservent l’état commercial au moment de l’achat.
- Les remboursements et changements de statut doivent être nommés comme transitions métier.

## Temps

Les dates qui changent une règle métier doivent être passées explicitement ou générées dans un workflow identifié. Eviter les appels dispersés à `new DateTimeImmutable()` dans les entités critiques lorsqu’une horloge applicative est nécessaire.

