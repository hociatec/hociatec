# Conventions De Nommage

Ce fichier complète `backend/docs/architecture-naming.md` et fixe les règles utilisées dans les contrôles d’architecture.

- `Handler` exécute une intention d’écriture.
- `Workflow` orchestre un cycle métier ou une transition.
- `Policy` répond à une question d’autorisation ou de règle sans effet de bord.
- `Factory` construit un objet cohérent.
- `Provider` exécute une lecture ou agrégation.
- `Projection` représente un modèle de lecture indépendant du transport.
- `Formatter` transforme un état applicatif en tableau ou document.
- `Port` définit un contrat applicatif implémenté par l’infrastructure.
- `Input` décrit des données validées issues de l’UI.
- `Command` décrit une intention d’écriture stable.
- `Data` est réservé aux objets de transfert internes simples.

Les suffixes `Manager`, `Helper` et `Utils` sont interdits dans les modules pour les nouveaux fichiers. Choisir un nom qui décrit la responsabilité réelle.

