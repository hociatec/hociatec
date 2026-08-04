# Architecture Naming

Use suffixes consistently so class names describe responsibility.

## Data Objects

- `DTO`: typed data crossing an application boundary. It may normalize raw HTTP payloads and carry Symfony validation constraints, but it must not be used as a domain entity.
- `Input`: avoid as a directory for new code. If the name is useful for a single class, keep it as an HTTP-facing DTO and convert it before domain logic.
- `Command`: reserved for console or message commands under `Infrastructure`, or for explicit application command objects if a command bus is introduced.
- `Query`: reserved for explicit read requests if a query bus is introduced. Read-side services currently use `Provider` or `Projection`.

## Mapping

- `Projection`: read-side payload mapping independent of transport details.
- `Formatter`: maps domain state to arrays for application views or documents. Application formatters live under `Application/Projection`.
- `ResponseMapper`: maps an application projection to final HTTP JSON under `UI/Response` or `UI/Http/Response`.
- `Mapper`: transforms one data shape into another without side effects.

## Behavior

- `Handler`: executes an explicit use case, event, command, or webhook action.
- `Provider`: provides read-side data or aggregation.
- `Writer`: creates or mutates a resource from typed data.
- `Updater`: mutates one already-loaded resource in a narrow way.
- `Calculator`: performs pure calculation without persistence or I/O.
- `Policy`: answers authorization or business-rule decisions without side effects.
- `Workflow`: orchestrates a lifecycle or state transition.
- `Gateway`: port toward an external system.
- `Repository`: loads and saves an aggregate; complex admin/public searches should move to query traits, providers, or projections.
- `Persistence`: narrow persistence wrapper around save/remove/commit mechanics.
- `Service`: reserved for cohesive domain/application behavior that does not fit a narrower name.
- `Manager`: avoid for new code because it is too generic; migrate existing managers toward one of the suffixes above when touched.
