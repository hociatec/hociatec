# Checklist de revue frontend

Utiliser cette checklist avant fusion d'une PR frontend.

## Securite et permissions

- Les permissions sont verifiees cote backend, le frontend ne sert pas de barriere unique.
- Aucune donnee sensible n'est stockee dans `localStorage`, `sessionStorage`, logs ou URL.
- Les redirections utilisateur passent par la validation stricte des chemins autorises.
- Les liens externes ouverts dans un nouvel onglet utilisent `rel="noopener noreferrer"`.
- Les apercus HTML, exports CSV et contenus riches passent par les helpers de securisation existants.

## Donnees et API

- Les types OpenAPI sont regeneres quand le contrat backend change.
- Les appels API passent par le client HTTP partage.
- Les uploads ne forcent pas manuellement un `Content-Type` JSON ou multipart global.
- Les erreurs API sont normalisees avant affichage.
- Les mutations React Query invalident les cles impactees.
- Les requetes repetitives ont une limite, un intervalle raisonnable ou une condition d'arret.

## UX

- Chaque page distante gere loading, erreur et etat vide.
- Les actions longues affichent un etat pending et bloquent les doubles clics.
- Les actions destructrices utilisent un dialogue applicatif.
- Les listes administrables sont paginees ou virtualisees.
- Les dates, prix, quantites et statuts utilisent les formatteurs communs.

## Accessibilite

- Les controles interactifs ont un nom accessible.
- Les erreurs de formulaire sont annoncees et liees aux champs.
- Les dialogues gerent titre, focus et fermeture clavier.
- Les contrastes et etats focus restent visibles.
- Les tests axe sont ajoutes ou ajustes pour les composants partages modifies.

## Maintenabilite

- La feature n'importe pas les fichiers internes d'une autre feature.
- Les nouveaux composants reutilisables vivent dans `shared`.
- Les gros composants sont decoupes avant d'ajouter une logique supplementaire.
- Les noms suivent les conventions `*Api.ts`, `use*Query.ts`, `use*Mutation.ts`, `*Page.tsx`, `*Dialog.tsx`, `*.types.ts`, `*.schema.ts`, `*.mapper.ts`.
- Le code mort, les variantes CSS obsolete et les exports inutilises sont supprimes pendant la modification concernee.

## Validation

- `npm run quality` passe localement.
- Les tests ajoutent une assertion sur le comportement important, pas seulement sur le rendu initial.
- Les changements visuels importants sont verifies en responsive.
- Les risques residuels sont mentionnes dans la PR.
