# Décision 0002: Rôle Du Module Admin

`Admin` est une couche de composition UI/applicative pour les parcours internes.

Il peut appeler les ports et workflows des autres modules, agréger des projections et appliquer des autorisations d’administration. Il ne doit pas devenir propriétaire des règles métier de `Catalog`, `Order`, `Quote`, `TradeIn` ou `User`.

