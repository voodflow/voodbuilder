# Phase 4 — Conditions module pilot

## Work completed

- `ConditionsModule` behind `ModuleRegistry` (`voodbuilder.modules.conditions.enabled`)
- Public render ignores condition rules when disabled (keeps all blocks, strips attrs)
- Editor receives `conditionsEnabled` / empty `conditionOptions` when disabled
- Inspector skips conditions UI registration when disabled

## Tests

`tests/Modules/ConditionsModuleTest.php`  
existing `GrapesJsConditionsRenderTest` (module enabled path)

## Acceptance

- Enabled: locale filtering still works
- Disabled: both EN/IT blocks remain visible; Core boots
