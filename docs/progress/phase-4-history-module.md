# Phase 4 — Module registry pilot (History)

## Work completed

- `HistoryModule` registers revision routes via `RegistersRoutes`
- ServiceProvider boots `ModuleRegistry` with History enabled by default
- Revision recording and editor revision URLs gated by `HistoryModule::isEnabled()`
- Config: `voodbuilder.modules.history.enabled`

## Acceptance

- History enabled: revision Feature tests pass
- History disabled: revision routes absent; page save still works; no revision rows

## Tests

`tests/Modules/HistoryModuleTest.php`  
`tests/Feature/EditorPageRevisionTest.php`  
`tests/Contracts/ModuleRegistryTest.php`

## Next

Phase 4 cont. or Phase 5: Conditions pilot, then editor registries.
