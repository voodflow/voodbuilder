# Phase 0 — Freeze and baseline

**Branch:** `refactor/modular-architecture` (from `modular`)  
**Package version at freeze:** `0.0.11` (`composer.json`)  
**Git tag `0.0.11`:** points to `ec0b242` (historical release; HEAD is newer on the 0.0.11 line)  
**Baseline HEAD at phase start:** recorded in this progress file after audit commit

## Work completed

- Created branch `refactor/modular-architecture`.
- Created `docs/audit/`, `docs/progress/`, `docs/baseline/` tree.
- Ran full PHPUnit suite as freeze baseline.
- Added frontend Vitest CI job (see `.github/workflows/tests.yml`).
- Exported representative HTML/JSON fixtures under `docs/baseline/`.

## Tests executed

```text
vendor/bin/phpunit --configuration phpunit.xml.dist
Result: 561 tests, 1711 assertions, 16 failures, 3 skipped

npm run test:js
Result: 23 tests, 1 failure (resolveSettings active settings root)
```

## Known baseline failures (must not “fix by rewrite” during audit)

Documented in `docs/audit/risk-register.md`. Failures observed during Phase 0 run include chrome/footer renderer expectations and related Editor unit tests (e.g. `EditorServerBlockRendererTest`, `SitePageEditorTest`). These are characterisation debt for Phase 2, not silent ignore.

## Screenshots / live HTML / API captures

Not captured from a running host in this phase (no stable local demo session required for audit). Placeholders:

- `docs/baseline/html/` — static chrome/footer samples from unit expectations
- `docs/baseline/editor-json/` — attribute/contract samples
- `docs/baseline/api/` — route inventory snapshots
- Screenshots: deferred to Phase 2 characterisation (Playwright / manual) once green fixtures exist

## Rollback

```bash
git checkout modular
# or reset to the commit recorded as pre-audit baseline
```

## Next

Phase 1 audit documents → then Phase 2 characterisation tests targeting the 16 failures and uncovered flows.
