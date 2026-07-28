# Phase 7 — Entitlement system

## Delivered

- `src/Licensing/` with `CapabilitySet`, `LicenceStatus`, `EntitlementManager`
- Contract `EntitlementProvider`
- Providers: `ConfigEntitlementProvider`, `TestingEntitlementProvider`, `CachedEntitlementProvider`
- Edition matrix: `EditionCapabilityMatrix` (community / professional / agency)
- Facade: `Voodbuilder::can()`, `Voodbuilder::cannot()`, `Voodbuilder::entitlements()`
- Config: `voodbuilder.license.edition` (`VOODBUILDER_EDITION`, default `community`)
- Testbench defaults to `agency` + cache off so in-repo Agency surfaces keep working

## Not in this phase

- AnyStack remote adapter (Phase 10)
- Hard Composer package splits

## Acceptance

Plan matrices are simulable in `tests/Licensing/EntitlementManagerTest.php`.
