# Phase 3 — Public contracts

## Work completed

Introduced module system contracts without migrating features:

- `VoodBuilderModule`
- Contributor interfaces: Filament resources, routes, blocks, conditions, assets
- `ModuleContext`, `ModuleRegistry`, `AbstractVoodBuilderModule`
- `Voodbuilder::modules()` accessor
- Registry singleton registered in ServiceProvider (empty; no behavioural change)

## Tests

`tests/Contracts/ModuleRegistryTest.php` — lifecycle, disable, dependencies, contributors.

## Acceptance

No visible product behaviour change; existing Feature/Unit suite remains the behaviour source of truth.

## Next

Phase 4 — register History (or Conditions) as first internal pilot module behind the registry.
