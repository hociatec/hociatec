# Exceptions d'architecture acceptees

Ce fichier documente les ecarts structurels conserves volontairement. Un ajout ici doit rester rare et decrire pourquoi la dette est acceptable.

## Entites Doctrine volumineuses

- `User`, `Voucher`, `Product`, `Quote`, `OrderCheckoutSession`, `TradeInRequest` : ces agregats portent encore une surface de lecture importante parce que les projections, formulaires admin et tests existants dependent de getters stables. Les mutations sensibles doivent rester exprimees par des methodes metier ou des value objects, pas par de simples setters non valides.
- Les traits d'accessors sont acceptes uniquement comme separation de domaines fonctionnels lisibles. Ils ne doivent pas redevenir un trait monolithique de plusieurs centaines de lignes.

## Constructeurs a plusieurs parametres

- Les DTO d'entree proches du HTTP peuvent conserver plusieurs champs scalaires si `fromArray()` centralise la normalisation et si la validation Symfony porte les invariants utilisateur.
- Les value objects metier ne doivent pas melanger plusieurs concepts. Quand un constructeur depasse 6 ou 7 parametres, extraire un sous-objet avant d'ajouter un nouveau champ.

## Dates Doctrine

- `PrePersist` et `PreUpdate` sont reserves aux dates techniques `createdAt` et `updatedAt`.
- Les dates metier comme paiement, publication, cloture, expiration ou facturation doivent etre fixees par une methode applicative ou domaine explicite.

## Monnaie et montants

- Les nouveaux montants doivent passer par `App\Shared\Domain\ValueObject\Money`.
- Les nouveaux codes monnaie doivent passer par `App\Shared\Domain\ValueObject\Currency`.
- Les anciens setters publics de compatibilite peuvent rester s'ils deleguent vers un comportement metier ou un value object validant.
