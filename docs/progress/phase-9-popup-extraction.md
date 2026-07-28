# Phase 9 — Popup extraction prep (in-repo)

Physical Composer package `voodflow/voodbuilder-popups` is **not** split yet. This phase hardens the in-package boundary so Core survives without Popups.

## Acceptance covered here

| Requirement | Status |
|---|---|
| Core works without Popups | `PopupsModule` disable removes routes/resource/editor URLs |
| Public render does not fail if absent | `popups-boot` checks module + `Route::has` before emitting config; no `route()` on missing names |
| Existing popup data preserved | DB rows untouched when module off |
| Admin orphan warning | `PopupsOrphanStatus` + Filament notification on Settings mount |
| Public API no-op | `PopupsPublicController` returns `[]` when module disabled (defensive) |

## Target future package layout

```text
voodflow/voodbuilder-popups
├── src/
│   ├── PopupsServiceProvider.php
│   ├── PopupsPlugin.php          # Filament PopupResource
│   └── ... (controllers, models stay until extract)
├── resources/js/{popups-ui,popups-runtime}.js
└── composer.json
```

Core keeps:

- optional `<x-voodbuilder::popups-boot />` stub that no-ops without routes
- `PopupsOrphanStatus` warning helper
- entitlement `popups.*` in Community matrix (optional commercial later)

## Next for true extract

1. Move popup PHP/JS/migrations into the new package
2. Core depends optionally via Composer suggest
3. Hosts install `voodflow/voodbuilder-popups` to restore features

## Tests

- `tests/Modules/PopupsModuleTest.php` (disable + orphan + boot partial)
