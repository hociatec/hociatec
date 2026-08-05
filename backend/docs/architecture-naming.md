# Architecture Naming

Use suffixes consistently so class names describe responsibility.

## Data Objects

- `DTO`: generic compatibility suffix for existing typed data crossing an application boundary. New code should prefer a narrower suffix below when the role is clear.
- `Input`: HTTP-facing validated data created from a request body, query string, or multipart payload.
- `Command`: an explicit write intent passed to an application handler or workflow.
- `Query`: an explicit read request passed to a provider or query handler.
- `Result`: an application result returned by a workflow when the shape is not a transport response.
- `ViewModel`: a UI-facing representation when a screen needs a stable read model independent of Doctrine entities.

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
