# Stratégie de dépréciation des composants

Objectif
- Réduire le risque de régression lors du nettoyage progressif de l’ancienne UI.

Règles
- Déprécier seulement via une annotation explicite `@deprecated` sur l’export.
- Fournir une alternative recommandée dans la même déclaration.
- Ajouter un log console en développement uniquement (optionnel) pour détecter les usages actifs.
- Supprimer un composant au moins 4 semaines après sa dépréciation, seulement quand aucune importation active n’est trouvée.

Processus de migration
1. Identifier un composant ancien via un inventaire hebdomadaire.
2. Marquer le fichier en `@deprecated` et pointer la nouvelle API.
3. Mettre à jour l’importation des usages critiques (admin, compte, public) en priorité.
4. Ajouter ou migrer les tests de smoke.
5. Conserver une période d’observation de télémétrie d’au moins 2 releases.
6. Supprimer le composant ancien uniquement après stabilisation.

Métriques minimales
- Nombre d’utilisations restantes sur `src`.
- Répartition des usages par zone (public / compte / admin).
- Nombre de bugs remontés suite à la migration.
