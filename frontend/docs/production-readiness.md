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
- Le statut hors connexion est annonce globalement par `NetworkStatusBanner`; React Query relance les donnees au retour reseau.
- La politique de cache globale React Query fixe `staleTime` a 60 secondes, `gcTime` a 5 minutes et des retries limites aux erreurs recuperables. `refetchOnWindowFocus` est desactive par defaut et doit etre reactive localement uniquement pour les donnees qui le justifient.
- Les budgets bundle sont verifies par `npm run check:bundle-budget`.
- Les tests couvrent auth, commandes, paiement, contrats de donnees et formulaires. Les tests d'accessibilite axe sont isoles dans `npm run test:a11y` et executes par `npm run quality`.
- MSW et Playwright sont disponibles pour les tests de parcours plus larges.
- L'observabilite applicative est centralisee et nettoie les contextes avant emission. L'echantillonnage Sentry est pilote par `VITE_SENTRY_TRACES_SAMPLE_RATE` avec une valeur par defaut de 5%, et la retention doit etre configuree cote fournisseur d'erreurs selon l'environnement.
- Chaque requete HTTP porte `X-Frontend-Request-Id`; les erreurs utilisateur affichent le `requestId` backend quand il existe, puis l'identifiant frontend en fallback.
- Les Web Vitals sont mesures au demarrage de l'application et envoyes vers `VITE_WEB_VITALS_ENDPOINT` quand il est configure.

## Structure

- Les regles d'import entre features sont controlees par `npm run check:architecture`.
- Les cycles sont controles par `npm run check:cycles`.
- Les conventions de nommage et de dette sont documentees dans `frontend/docs/maintainability.md`.
- Les en-tetes CSP doivent rester presents dans `frontend/public/_headers` et `deploy/nginx/frontend-security-headers.conf`; `npm run check:production-safeguards` le verifie.
- Les polices utilisent la stack systeme definie par `--font-family-sans`. Aucun fichier webfont n'est charge: pas de sous-ensemble, preload ou `font-display` a maintenir tant que cette strategie reste en place.
- La CI racine execute `npm run quality` pour le frontend.
