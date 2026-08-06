# Architecture frontend

Ce document fixe les conventions attendues pour faire evoluer le frontend React / TypeScript.

## Structure

- `src/app` contient l'assemblage applicatif : routes, providers, layout global et styles transverses.
- `src/features/<feature>` contient le code metier d'une fonctionnalite : pages, composants, hooks, API, schemas et types locaux.
- `src/shared` contient uniquement du code reutilisable sans dependance vers `features`.
- Les exports inter-features passent par un point d'entree public : `publicApi.ts`, `adminApi.ts`, `typesApi.ts` ou `uiApi.ts`.
- Une feature ne doit pas importer un fichier interne d'une autre feature.

## Imports

- `shared` ne depend jamais de `features`.
- `features` peut dependre de `shared`.
- `app` orchestre les features et peut importer leurs points d'entree publics.
- Les imports relatifs profonds restent limites au dossier courant.
- Les cycles sont interdits et controles par `npm run check:cycles`.
- Les frontieres de features sont controlees par `npm run check:architecture`.

## Client HTTP

- Les appels reseau passent par les helpers HTTP partages.
- Ne pas recreer de client Axios local dans une page ou un composant.
- Ne pas definir de `Content-Type: application/json` global pour les uploads : le navigateur doit poser la boundary `multipart/form-data`.
- Les erreurs reseau sont normalisees avec les helpers partages avant d'etre affichees.
- Les URLs de redirection doivent passer par les helpers de validation de redirection.

## Erreurs et etats

- Toute page distante expose au minimum un etat loading, un etat erreur et un etat vide pertinent.
- Les erreurs affichables a l'utilisateur doivent etre issues d'une normalisation commune.
- Les actions destructrices utilisent un dialogue applicatif, pas `window.confirm`.
- Les actions admin sensibles doivent afficher un etat pending et empecher les doubles soumissions.

## Authentification et securite

- Les permissions reelles restent verifiees cote backend.
- Le frontend ne stocke pas de secret, jeton sensible ou donnee confidentielle dans `localStorage` ou `sessionStorage`.
- L'option "Se souvenir de mon email" conserve uniquement l'adresse email dans `localStorage`, via le wrapper de stockage partage, avec une expiration applicative de 30 jours. Aucun mot de passe, jeton ou secret n'est conserve avec cette option.
- Les pages privees utilisent des metadonnees `noindex`.
- Les liens externes ouverts dans un nouvel onglet utilisent `rel="noopener noreferrer"`.
- Les exports CSV doivent passer par le helper de telechargement qui neutralise les formules.

## React Query

- Les donnees serveur sont chargees avec React Query sauf cas statique justifie.
- Les cles de cache sont stables et centralisees quand elles sont partagees.
- Les mutations invalident explicitement les donnees impactees.
- Les polling et refetch automatiques doivent avoir une condition d'arret ou une frequence documentee.
- Les requetes longues ou navigables doivent pouvoir etre annulees par `AbortSignal` quand l'API le permet.

## Design system

- Les composants UI communs vivent dans `src/shared/components/ui`.
- Les pages reutilisent les composants d'etat partages pour loading, erreur, interdit et introuvable.
- Les formats de dates, prix, statuts et libelles passent par des helpers ou mappings partages.
- Les variantes visuelles nouvelles doivent etre ajoutees au composant commun avant de dupliquer du CSS local.

## Tests

- Les helpers de validation, mapping, formatage, securite et contrats API ont des tests unitaires.
- Les parcours critiques sont couverts : auth, paiement, commande, permissions, formulaires admin.
- Les tests d'accessibilite ciblent les composants partages et les pages a fort trafic.
- Les evolutions de contrat API doivent regenerer les types OpenAPI avec `npm run generate:api-types`.

## Conventions API

- Les fichiers d'appel API suivent les suffixes documentes dans `docs/maintainability.md`.
- Les DTO entrants et sortants sont types.
- Les conversions entre API et modele UI sont isolees dans des mappers quand la forme diverge.
- Les schemas de validation vivent pres des formulaires ou dans un fichier `*.schema.ts` quand ils sont partages.
