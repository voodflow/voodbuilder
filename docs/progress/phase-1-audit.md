# Phase 1 — Architectural audit

## Work completed

All required audit documents under `docs/audit/`:

- architecture-current-state.md
- php-module-map.md
- editor-js-inventory.md
- editor-event-map.md
- editor-command-map.md
- editor-global-state.md
- database-map.md
- route-map.md
- filament-registration-map.md
- asset-build-map.md
- public-api-candidates.md
- plugin-boundary-candidates.md
- test-coverage-map.md
- risk-register.md
- migration-plan.md

## Files changed

Documentation + baseline snapshots + frontend CI only (no production PHP/JS behaviour reorganisation).

## Tests added

None (audit phase). Vitest job added to CI for existing smoke file.

## Tests executed

PHPUnit baseline recorded in Phase 0 progress (16 failures known).

## Known issues

- Suite not green at freeze (R3).
- Live screenshots / full HTML dumps / API response corpora still thin.

## Remaining legacy

Entire monolith — expected.

## Rollback

Delete `docs/audit` / `docs/baseline` / `docs/progress` commits; restore workflow if needed.

## Next phase

Phase 2 — Characterisation tests: green the 16 failures, add fixtures under `tests/Fixtures/0.0.11/`, expand Feature coverage per master plan §20.
