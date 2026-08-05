# Priorites de production frontend

Ce document relie les priorites d'audit aux garanties actuellement en place.

## Corrige immediatement

- Redirections : les chemins internes passent par `isSafeInternalRedirectPath`; les redirections externes passent par `redirectToTrustedUrl` et `isTrustedRedirectUrl`.
- Uploads : le client HTTP partage ne definit pas de `Content-Type` global, ce qui laisse `FormData` poser correctement les boundaries multipart.
- Permissions : les routes et boutons admin restent des aides UX; les decisions d'autorisation sont attendues cote PHP.
- HTML et liens externes : `dangerouslySetInnerHTML` est interdit par controle automatique, et les liens `target="_blank"` doivent porter `rel="noopener noreferrer"`.

## Generalise

- React Query porte les donnees serveur et les mutations critiques.
- Les types API sont generes depuis OpenAPI par `npm run generate:api-types` et verifies par `npm run check:api-contract`.
- Les composants UI partages couvrent les etats de page, formulaires, dialogues et pagination.
- Les gros composants sont surveilles par `npm run check:maintainability`.
- Les requetes navigables peuvent recevoir un `AbortSignal` via `requestSignalConfig`.
- Les tests couvrent auth, commandes, paiement, contrats de donnees, formulaires et accessibilite de composants partages.
- MSW et Playwright sont disponibles pour les tests de parcours plus larges.
- L'observabilite applicative est centralisee et nettoie les contextes avant emission.

## Structure

- Les regles d'import entre features sont controlees par `npm run check:architecture`.
- Les cycles sont controles par `npm run check:cycles`.
- Les conventions de nommage et de dette sont documentees dans `docs/maintainability.md`.
- La CI racine execute `npm run quality` pour le frontend.
