# Architecture Naming

Use suffixes consistently so class names describe responsibility.

- `Input` / `DTO`: typed data crossing a boundary. It may normalize raw payloads and carry Symfony validation constraints.
- `Formatter`: maps domain state to API or document output arrays. Controllers should prefer formatters over inline entity mapping.
- `Writer`: creates or mutates a resource from a typed input.
- `Handler`: handles an event, command, or explicit application action.
- `Policy`: answers authorization or business-rule decisions without side effects.
- `Provider`: read-side access or aggregation.
- `Persistence`: narrow persistence wrapper around save/remove/commit mechanics.
- `Service`: reserved for cohesive domain/application behavior that does not fit the narrower names.
- `Manager`: avoid for new code because it is too generic; migrate existing managers toward one of the suffixes above when touched.
