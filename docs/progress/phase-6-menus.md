# Phase 6 — Menus module

## Boundary

- `MenusModule` owns admin preview route + Filament menu-tree assets
- Filament `NavigationMenuResource` gated via `voodbuilder.modules.menus.enabled` in `VoodbuilderPlugin`
- Public navigation resolution (`Navigation::items`, models, site blocks) remains Core

## Config

`voodbuilder.modules.menus.enabled` / `VOODBUILDER_MODULE_MENUS`

## Tests

- `tests/Modules/MenusModuleTest.php`
- Existing `NavigationMenuPreviewTest` still green with module enabled

## Remaining Phase 6 order

Layouts → Pages → Dynamic Data → Components → Popups
