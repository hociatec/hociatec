# Architecture Backend

## Couches

- `UI` contient les contrôleurs, mappers de requête, réponses HTTP et détails de transport.
- `Application` contient les cas d’usage, ports, projections, politiques applicatives, workflows et DTO indépendants du transport.
- `Domain` contient les entités, enums, value objects, invariants et règles métier pures.
- `Infrastructure` contient Doctrine, filesystem, PDF, mailer, HTTP client, cookies, messages et adaptateurs externes.

## Direction Des Dépendances

`UI` dépend de `Application`.

`Application` dépend de `Domain`, de ses propres ports et des abstractions partagées.

`Infrastructure` implémente les ports de `Application`.

`Domain` ne dépend pas de `UI`, `Application` ou `Infrastructure`, hors compromis Doctrine documenté dans les décisions.

## Règles Protégées

- Aucun objet `Symfony\Component\HttpFoundation` dans `Application`.
- Aucun accès Doctrine direct dans `UI` ou `Application`.
- Les fichiers privés passent par des ports.
- Les contrôleurs ne décodent pas le JSON directement et utilisent les helpers HTTP partagés.
- Les listes exposées par l’API doivent être paginées côté backend.

