# Conventions De Migration Backend

## Principes

- préférer des migrations petites, relisibles et réversibles quand c’est réaliste ;
- expliciter `UNIQUE`, `FK`, `NOT NULL`, index et `ON DELETE` au niveau base lorsque l’invariant est important ;
- éviter les changements destructifs sans garde préalable ;
- documenter toute exception où une migration n’est pas raisonnablement réversible.

## Compatibilité production

Pour limiter le risque de downtime :

- ajouter d’abord une colonne nullable ou avec valeur par défaut compatible ;
- backfiller ensuite ;
- rendre le champ obligatoire dans une migration séparée si nécessaire ;
- éviter les changements incompatibles avec l’ancienne version du code pendant un déploiement progressif ;
- privilégier des indexes créés explicitement pour les lectures critiques.

## Revue avant mise en production

Avant déploiement :

- exécuter les migrations sur une base de validation ;
- vérifier la durée et le volume des écritures ;
- confirmer que la version applicative précédente peut encore fonctionner pendant la fenêtre de déploiement ;
- valider ensuite `cache:warmup` et les parcours critiques.
