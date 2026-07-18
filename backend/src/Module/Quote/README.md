Quotes module

Endpoints (Admin)
- GET `/api/admin/quotes` list with `q` and `status` filters
- POST `/api/admin/quotes` create from JSON payload
- GET `/api/admin/quotes/{id}` fetch one
- POST `/api/admin/quotes/{id}` update (autosave-friendly)
- DELETE `/api/admin/quotes/{id}` delete
- POST `/api/admin/quotes/{id}/duplicate` duplicate
- POST `/api/admin/quotes/{id}/pdf` generate PDF
- POST `/api/admin/quotes/{id}/send-email` send by email

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
- Uses the accessible PDF renderer configured by `QuotePdfService`.

Email sending
- Sends the quote by email through the configured transport.
- The PDF is attached when generation succeeds.
- If PDF generation fails, the email is still sent without attachment when possible.

