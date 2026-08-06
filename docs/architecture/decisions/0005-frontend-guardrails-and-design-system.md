# ADR 0005 - Garde-fous frontend et design system

## Statut

Accepté.

## Contexte

Le frontend React contient plusieurs domaines publics, client et admin. Les audits frontend ont identifié des risques récurrents : imports inter-features profonds, styles globaux difficiles à maîtriser, comportements navigateur dupliqués, alertes d’accessibilité corrigées après rendu, et erreurs utilisateur peu homogènes.

## Décision

- Les imports inter-features passent par `publicApi.ts`, `adminApi.ts`, `typesApi.ts` ou `uiApi.ts`.
- `src/shared` ne dépend jamais de `src/features`.
- Les alertes et états utilisateur passent par les composants UI partagés.
- Les comportements navigateur sensibles sont centralisés : stockage, téléchargement, ouverture externe, génération aléatoire et idempotence.
- Les tokens de design vivent dans `frontend/src/app/styles/global/theme.css`.
- Les conventions du design system sont documentées dans `frontend/docs/design-system.md`.
- Les contrôles automatiques frontend doivent bloquer les régressions de sécurité et d’architecture.

## Conséquences

- Les nouvelles features doivent exposer explicitement leur surface publique.
- Les composants locaux ne doivent pas recréer des états loading, erreur, vide, interdit ou introuvable quand un composant partagé existe.
- Les styles historiques peuvent rester en place, mais les nouveaux styles doivent consommer les tokens et réduire progressivement les `!important`.
- Les exceptions doivent être justifiées en revue et, si elles deviennent durables, documentées.
