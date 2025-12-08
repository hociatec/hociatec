Audit Module

- Endpoints
  - Client
    - POST `/api/audits` create request (type, url, objectives)
    - GET `/api/audits` list my audits
    - GET `/api/audits/{id}` show my audit with checklist
    - POST `/api/audits/{id}/pdf` download detailed PDF
    - POST `/api/audits/{id}/pdf-summary` download summary PDF
  - Admin
    - GET `/api/admin/audits` list all
    - GET `/api/admin/audits/{id}` show
    - PUT `/api/admin/audits/{id}/status` update status
    - PUT `/api/admin/audits/{auditId}/items/{itemId}` update item compliance/comment
    - POST `/api/admin/audits/{id}/pdf` download detailed PDF
    - POST `/api/admin/audits/{id}/pdf-summary` download summary PDF

- PDF generation
  - Requires `dompdf/dompdf` to be installed in the backend:
    - `composer require dompdf/dompdf`
  - Without it, endpoints return HTTP 501 with an explicit message.

- Accessibility template
  - Includes WCAG levels (A/AA/AAA) aligned to RGAA coverage.
  - Levels are stored per checklist item and returned to client/admin UIs.

