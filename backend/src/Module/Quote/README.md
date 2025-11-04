Quotes module

Endpoints (Admin)
- GET `/api/admin/quotes` list with `q` and `status` filters
- POST `/api/admin/quotes` create from JSON payload
- GET `/api/admin/quotes/{id}` fetch one
- POST `/api/admin/quotes/{id}` update (autosave-friendly)
- DELETE `/api/admin/quotes/{id}` delete
- POST `/api/admin/quotes/{id}/duplicate` duplicate
- POST `/api/admin/quotes/{id}/pdf` generate PDF (stub until dompdf installed)
- POST `/api/admin/quotes/{id}/send-email` send by email (stub until mailer configured)

Services catalog (Admin)
- GET `/api/admin/quotes/services` list
- POST `/api/admin/quotes/services` create (FormData: title, description?, unit?, price, vatRate)
- POST `/api/admin/quotes/services/{id}` update (FormData)
- DELETE `/api/admin/quotes/services/{id}` delete

Public
- POST `/api/public/quotes` create quote request from JSON; status is forced to `sent`.

Numbering
- DEV-YYYY-#### with yearly counter from creation date.

PDF generation
- Requires an external library (e.g. `dompdf/dompdf`). After installing, implement a PDF service and call it from `GeneratePdfController`.

Email sending
- Install and configure `symfony/mailer` and a transport, then implement send logic in `SendQuoteEmailController`.

