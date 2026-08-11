# Audit Recherche Texte Et Timezone

## Recherche texte `%LIKE%`

Les recherches administratives et catalogue restent volontairement paginées et bornées. Les termes libres transmis aux requêtes `%LIKE%` sont limités côté backend pour éviter les charges aberrantes et garder des plans de requête prévisibles.

Hotspots audités :

- `Catalog/ProductCatalogFilterQueries` : scoring par préfixe sur `name`, `sku`, `brand`, `category`, puis `%LIKE%` seulement sur les champs textuels plus larges ;
- `UserAdminCustomerQueries` et `UserRepository` : recherche admin/client bornée, avec pagination stricte ;
- `NewsArticleRepository`, `TradeInRequestRepository`, `QuoteRepository`, `AuditRequestRepository` : recherche d’administration bornée et paginée ;
- `BetaTesterProfileRepository` et `BugReportRepository` : recherche SAV/beta bornée et exports limités.

Règle d’escalade :

- si une recherche doit scanner de gros volumes sur `description`, `content`, `excerpt` ou d’autres blobs textuels, elle doit être migrée vers un index dédié, une colonne normalisée plus ciblée, ou un moteur de recherche ;
- si le besoin est surtout un début de terme (`SKU`, nom, slug, référence), préférer une stratégie de préfixe plutôt qu’un `%LIKE%` global ;
- tout nouvel endpoint de recherche libre doit garder une pagination bornée et un terme normalisé.

## Timezone UTC

La politique du backend reste :

- instants persistés en UTC ;
- formats API en `DATE_ATOM` ou ISO 8601 explicite ;
- conversions locales uniquement à l’entrée/sortie ;
- timestamps externes de type epoch réinterprétés explicitement en UTC ;
- aucune logique métier ne doit dépendre d’une timezone locale implicite.

Points validés :

- les projections et APIs exposent les dates au format `DATE_ATOM` ;
- les conversions d’affichage local restent limitées aux notifications/templates qui assument explicitement une timezone de présentation ;
- les workflows métiers critiques utilisent `ClockInterface` pour l’instant courant, ce qui rend les tests temporels reproductibles.
