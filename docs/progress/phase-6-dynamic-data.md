# Phase 6 — Dynamic Data module

## Boundary

- `DynamicDataModule` owns bindings catalog/preview routes and model-integration registrar refresh
- Filament `ModelIntegrationResource` gated via `modules.dynamic_data.enabled`
- Editor `bindingsUrl` / `bindingsPreviewUrl` null when disabled
- Builtin binding sources + public BindingRenderer remain Core

## Config

`voodbuilder.modules.dynamic_data.enabled` / `VOODBUILDER_MODULE_DYNAMIC_DATA`

## Tests

- `tests/Modules/DynamicDataModuleTest.php`

## Remaining Phase 6 order

Components → Popups
