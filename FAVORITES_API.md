# API Favoris

Documentation des endpoints favoris exposes par le backend Symfony (`backend/src/Module/Favorite`).

## 1. GET `/api/favorites`

- **Acces** : utilisateur connecte (`ROLE_USER`).
- **But** : lister les produits favoris de l'utilisateur courant.
- **Reponse 200** :
  ```json
  {
    "status": "success",
    "data": {
      "items": [
        {
          "addedAt": "2025-12-09T12:10:00+01:00",
          "product": { /* format CatalogFormatter::formatProduct */ }
        }
      ]
    }
  }
  ```
- **Implementation** : `ListFavoritesController` utilise `FavoriteService::listForUser` et renvoie pour chaque favori la date d'ajout + les infos produit (formatter catalogue classique).

## 2. POST `/api/favorites/{productId}`

- **Acces** : utilisateur connecte (`ROLE_USER`).
- **But** : ajouter un produit publie aux favoris (idempotent).
- **Validation** :
  - `productId` doit pointer vers un produit existant et `isPublished = true`.
  - S'il existe deja un favori pour `(user, product)`, on renvoie quand meme 200 avec `alreadyFavorite: true`.
- **Reponses** :
  - **201** si creation :
    ```json
    {
      "status": "success",
      "data": {
        "favorite": {
          "product": { /* format catalog */ },
          "addedAt": "2025-12-09T12:10:00+01:00"
        },
        "alreadyFavorite": false
      }
    }
    ```
  - **200** si deja present (`alreadyFavorite: true`).
  - **404** si produit introuvable/non publie.
  - **401** si non authentifie.
- **Implementation** : `AddFavoriteController` charge le produit via `ProductRepository`, appelle `FavoriteService::addProduct` qui renvoie `(favorite, created)` et formate la reponse.

## 3. DELETE `/api/favorites/{productId}`

- **Acces** : utilisateur connecte (`ROLE_USER`).
- **But** : retirer un produit des favoris (idempotent).
- **Reponse 200** :
  ```json
  {
    "status": "success",
    "data": { "removed": true }
  }
  ```
- **Implementation** : `RemoveFavoriteController` tente de charger le produit puis `FavoriteService::removeProduct`. Pas d'erreur si le produit ou le favori n'existe pas.

## Couche metier et persistance

- Entite `Favorite` (`backend/src/Module/Favorite/Entity/Favorite.php`) : associe un utilisateur a un produit avec contrainte d'unicite `(user_id, product_id)` et champ `createdAt`.
- Repository `FavoriteRepository` : helpers `findOneByUserAndProduct`, `existsForUserAndProduct`, `findFavoritesForUser`.
- Service `FavoriteService` : logique d'ajout/liste/suppression, flush Doctrine.
- Migration `migrations/Version20251209120000.php` : creation de la table `user_favorites` + FK vers `users` et `catalog_products`.

## Notes d'implementation

- Tous les endpoints utilisent `ApiResponse` pour unifier les payloads (`status`, `data`).
- Les donnees produit retournees sont celles de `CatalogFormatter::formatProduct`, donc compatibles avec le front existant.
- Le front consomme aussi ces endpoints (`frontend/src/features/favorites/`), prevoir CORS/headers si besoin.
