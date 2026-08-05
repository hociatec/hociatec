# Maintenabilite frontend

Ce fichier decrit les limites de maintenabilite utilisees pour guider les refactorisations. Elles ne remplacent pas le jugement de revue, mais elles evitent que les dettes grossissent silencieusement.

## Conventions de noms

- `*Api.ts` : appels HTTP orientes domaine.
- `api.ts` : accepte uniquement comme point d'entree local d'une petite feature ou compatibilite historique.
- `*Query.ts` : hook de lecture React Query.
- `*Mutation.ts` : hook de mutation React Query.
- `*Page.tsx` : page routee.
- `*Dialog.tsx` : dialogue modal.
- `*.types.ts` : types partages par plusieurs fichiers.
- `*.schema.ts` : schema de validation.
- `*.mapper.ts` : conversion entre DTO API et modele UI.
- `publicApi.ts`, `adminApi.ts`, `typesApi.ts`, `uiApi.ts` : points d'entree autorises entre features.

## Limites surveillees

`npm run check:maintainability` signale :

- fichiers TypeScript ou TSX tres longs ;
- fichiers CSS tres longs ;
- fonctions ou composants avec trop de parametres ;
- densite elevee de branches conditionnelles ;
- usages de `window.confirm` ;
- fichiers API dont le nom ne documente pas clairement le role.

Les alertes de taille et complexite sont informatives. Les usages explicitement interdits peuvent bloquer la commande.

## Code mort

- Supprimer un export inutilise des qu'une modification touche le fichier.
- Supprimer les anciennes variantes de composants quand un composant commun les remplace.
- Preferer un helper partage a une copie locale de mapping ou formatage.
- Controler regulierement les dependances et exports inutilises avec un outil dedie lorsque la CI peut absorber le bruit initial.

## Refactorisation progressive

- Un composant qui depasse le seuil doit d'abord extraire les sous-parties stables : table, dialogue, formulaire, section de resume.
- Une fonction avec beaucoup de parametres doit recevoir un objet de commande ou un DTO type.
- Une page qui normalise des requetes, statuts ou filtres doit deleguer ce travail a un mapper ou hook dedie.
- Les refactorisations doivent conserver les tests du comportement existant avant d'etendre la couverture.
