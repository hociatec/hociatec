# Backend Transaction Conventions

Use `UnitOfWork::flush()` only for a simple use case that performs one coherent persistence change and has no external side effect to coordinate.

Use `TransactionManager::transactional()` when a use case combines several writes, writes plus file side effects, cache invalidation scheduling, outbox recording, payment state transitions, or any workflow that must be committed as one business operation.

When a transaction also needs file cleanup or any other compensating effect, register it through `TransactionSideEffectRegistry::afterCommit()` or `TransactionSideEffectRegistry::afterRollback()` instead of performing the external action directly in the middle of the SQL transaction.

When asynchronous work must follow a persisted business change, record an outbox event in the same transaction as the state change. A worker may then publish to Messenger or perform the external effect after the database commit.

Long-running reads must use bounded pagination, cursor pagination, or repository iterators. Avoid `findAll()` for data sets that can grow.
