# Phase 6 — Popups module

## Boundary

- `PopupsModule` owns GrapesJS popup CRUD/content routes, dedicated popup editor, and public runtime/analytics endpoints
- Filament `PopupResource` gated via `modules.popups.enabled` ∧ `popups.enabled`
- Editor popup URLs + `GrapesJsPopupEditorGate::canEdit` use `PopupsModule::isEnabled()`
- First internal pilot aligned with later `voodbuilder-popups` package extraction

## Config

`voodbuilder.modules.popups.enabled` / `VOODBUILDER_MODULE_POPUPS`  
(also requires legacy `voodbuilder.popups.enabled`)

## Tests

- `tests/Modules/PopupsModuleTest.php`

## Phase 6 complete

All planned internal module pilots are registered:

history → conditions → templates → themes → menus → layouts → pages → dynamic_data → components → popups

Next plan phases: entitlements (`VoodBuilder::can`), commercial boundaries, popup package extract.
