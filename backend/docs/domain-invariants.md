# Domain Invariants

Business entities reject invalid state instead of silently normalizing it.

## Monetary and Numeric Values

- Prices, discounts, taxes, refund amounts, review counts, stock thresholds, and loyalty balances cannot be negative.
- Quote quantities must be at least one.
- Percentage discounts are limited to the inclusive range 0 to 100.
- Invalid values raise `InvalidArgumentException` at the domain boundary.

## Lifecycle State

- Order, delivery, invoice, refund, checkout, support, and trade-in states accept only declared values.
- Order, appointment, quote, and trade-in lifecycle changes are handled by dedicated workflow classes or application workflows.
- Unknown enum-backed status values raise `ValueError`.

## Documents and Dates

- Invoice PDF and XML rendering are separate ports and renderers coordinated by `InvoiceDocument`.
- Invoice issuer information comes from the shared `InvoiceIssuerProfile`.
- Domain dates use `DateTimeImmutable`; string parsing stays at application or UI boundaries.
