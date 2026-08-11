# Backend Transaction Conventions

Use `UnitOfWork::flush()` only for a simple use case that performs one coherent persistence change and has no external side effect to coordinate.

Use `TransactionManager::transactional()` when a use case combines several writes, writes plus file side effects, cache invalidation scheduling, outbox recording, payment state transitions, or any workflow that must be committed as one business operation.

Production targets MySQL/InnoDB, not SQLite. The application does not override the database isolation level in Doctrine configuration, so the runtime expectation is the server default isolation level provided by MySQL/InnoDB. Critical write paths must therefore state their concurrency guarantees explicitly in code instead of relying on SQLite test behavior.

For Hociatec, the baseline rule is:
- keep the database default isolation for ordinary business transactions;
- add `PESSIMISTIC_WRITE` row locks for contested writes on an already identified aggregate;
- rely on unique constraints plus duplicate-key handling when the business invariant is keyed by a natural unique value instead of an existing row.

Current critical paths are:
- refresh tokens: `RefreshTokenRepository::findOneBySelectorForUpdate()` locks the selected token before rotation or revocation;
- stock and product administration: `ProductRepository::findForUpdate()` serializes stock mutations and threshold changes;
- refund processing and order state transitions: `RefundRequestRepository::findForUpdate()` and `OrderRepository::findForUpdate()` prevent duplicate processing on the same aggregate;
- appointments: `WorkingDayConfigurationRepository::findOneByDayForUpdate()` serializes booking checks for a given business day before slot allocation;
- training enrollment capacity: `TrainingSessionRepository::findForUpdate()` locks the targeted session before seat consumption;
- user account mutation flows: `UserRepository::findForUpdate()` protects concurrent account-side state changes;
- voucher creation: MySQL uniqueness on voucher codes is the final authority, and duplicate-key exceptions must be translated into a deterministic business error.

Inside a transaction, prefer one final `flush()` after all in-memory state changes. Add an intermediate `flush()` only when the next step needs a database-generated identifier or an already-materialized SQL side effect before the transaction ends.

When a transaction also needs file cleanup or any other compensating effect, register it through `TransactionSideEffectRegistry::afterCommit()` or `TransactionSideEffectRegistry::afterRollback()` instead of performing the external action directly in the middle of the SQL transaction.

When asynchronous work must follow a persisted business change, record an outbox event in the same transaction as the state change. A worker may then publish to Messenger or perform the external effect after the database commit.

Long-running reads must use bounded pagination, cursor pagination, or repository iterators. Avoid `findAll()` for data sets that can grow.
