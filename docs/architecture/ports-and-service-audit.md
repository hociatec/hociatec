# Audit Ports Et Services

## Ports à implémentation unique conservés

Le projet garde volontairement des ports avec une seule implémentation lorsqu'ils matérialisent une vraie frontière :

- persistance métier : `OrderRepositoryPort`, `QuoteRepositoryPort`, `UserRepositoryPort`, `TrainingSessionRepositoryPort` ;
- rendu documentaire : `OrderInvoicePdfRenderer`, `OrderInvoiceXmlRenderer`, `QuotePdfRenderer`, `AuditPdfRenderer`, `TradeInReceiptRenderer` ;
- sécurité et I/O : `UserPasswordHasher`, `TradeInPrivateFileStoragePort`, `OutboxRequestContextPort` ;
- intégrations externes : `StripeRefundClient`, `MarketingAudienceQuery`, `MarketingRecipientContextQuery`.

Ces ports restent utiles même avec une implémentation unique car ils séparent `Application` de Doctrine, du filesystem, du rendu PDF/XML ou d’un client externe. Un port purement pass-through sans frontière technique ou métier doit être supprimé.

## Services très fins conservés

Quelques classes applicatives courtes restent acceptées comme objets de composition explicites :

- `ProductWriteExecution`
- `ProductWriteGalleryPlan`
- `ProductWriteServices`
- `QuoteConversionPolicy`
- `RefundOperationPorts`
- `TrainingEnrollmentPorts`

Ces classes sont tolérées uniquement si elles regroupent des dépendances cohérentes ou rendent un workflow plus lisible. Un nouveau service applicatif très fin, utilisé une seule fois et sans frontière claire, doit être fusionné avec son appelant.

## Limites de complexité

- un constructeur applicatif ne devrait pas dépasser 9 dépendances ;
- les suffixes `Manager`, `Helper` et `Utils` ne sont pas acceptés pour de nouveaux services métier ;
- toute exception à ces règles doit être documentée dans les tests d’architecture ou dans une ADR.
