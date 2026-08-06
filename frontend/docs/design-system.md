# Design system frontend

## Strategie de styles

- Tailwind sert aux compositions locales et aux variantes simples de composants.
- Les classes metier CSS restent reservees aux surfaces complexes deja structurees par domaine.
- Les styles globaux vivent uniquement dans `src/app/styles/global` et `src/app/styles/chrome`.
- Les nouvelles couleurs, tailles, espacements, rayons et ombres passent d'abord par les tokens de `src/app/styles/global/theme.css`.
- Le responsive d'un composant ou d'une page doit rester dans son CSS colocalise quand il ne concerne pas le shell global.

## Tokens

- Couleurs de marque : `--hocia-*`.
- Couleurs shadcn/Tailwind : `--background`, `--foreground`, `--primary`, `--muted`, `--border`, `--ring`.
- Feedback : `--color-feedback-error-*`, `--color-feedback-success-*`, `--color-feedback-warning-*`, `--color-feedback-info-*`.
- Espacements : `--space-*`.
- Rayons : `--radius-*`.
- Ombres : `--shadow-*`.
- Typographie : `--font-family-sans`, `--font-size-*`, `--line-height-*`.
- Z-index : `--z-*`.

## Composants UI

- `Alert` est le composant unique pour les messages visibles. Il pose les roles ARIA et live regions selon la variante.
- `PageState`, `LoadingState`, `ErrorState`, `EmptyState`, `ForbiddenState` et `NotFoundState` standardisent les etats de page.
- `BlockingModal`, `dialog`, `alert-dialog`, `confirm` et `prompt` portent la strategie modale et doivent etre utilises avant de creer un dialogue local.
- `PaginationControls`, `FilterBar` et les filtres partages couvrent les listes repetitives.

## Accessibilite

- Les alertes doivent etre declaratives via `Alert` ou les etats de page, jamais corrigees apres coup par mutation DOM.
- Les layouts posent `data-page-focus-target` sur la cible de focus route.
- Les dialogues doivent verifier focus trap, fermeture clavier, restauration du focus et libelles accessibles.
- Les liens externes en nouvel onglet doivent garder `rel="noopener noreferrer"`.

## Formulaires

- Les nouveaux formulaires utilisent `react-hook-form` et isolent schema, modele initial, hook de controle et rendu.
- Les erreurs serveur passent par les helpers de `src/shared/forms`.
- Les gros formulaires doivent etre decoupes en sections de rendu et hook d'orchestration.

## Performance

- Ajouter un `staleTime` local plus long pour les donnees peu changeantes.
- Eviter les doubles sources de verite entre React Query et etat local.
- Les grandes listes doivent prouver leur pagination serveur avant d'ajouter de la virtualisation.
- Les routes lourdes restent chargees par domaine avec `lazyPage` et des frontieres `Suspense` locales quand l'ecran le demande.
