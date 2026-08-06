# Décision 0001: Doctrine Dans Domain

Les entités du domaine peuvent porter les attributs Doctrine et Validator.

Ce compromis garde le backend Symfony simple et évite une couche de mapping séparée lourde. En contrepartie, le domaine ne doit pas importer de services techniques, repositories, query builders, request/response HTTP ou filesystem.

## Exceptions Assumées

- Les attributs Doctrine et Validator sur les entités/DTO restent acceptés comme métadonnées déclaratives Symfony.
- Les services `Application` ne doivent pas dépendre directement de `MailerInterface`, `MessageBusInterface`, `CacheInterface`, `UserPasswordHasherInterface`, `Process` ou `ValidatorInterface`; ils passent par des ports applicatifs ou par des validateurs métier dédiés.
- Les constructions de dates dans `Application` sont tolérées uniquement pour parser une valeur utilisateur ou construire une borne relative locale. Les instants métier courants des workflows doivent utiliser `Psr\Clock\ClockInterface`.
- Les renderers PDF/XML restent en `Infrastructure`; leur découpage interne doit évoluer par sections sans replacer de logique de rendu dans `Application`.
