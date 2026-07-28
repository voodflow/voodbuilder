# Phase 6 — Layouts (Chrome) module

## Boundary

- `LayoutsModule` owns chrome layout editor + GrapesJS content-save routes and the content-slot block
- Filament `ChromeLayoutResource` gated via `modules.layouts.enabled` ∧ `chrome_layouts.enabled`
- Public shell resolution (`ChromeLayoutResolver`) remains Core on `chrome_layouts.enabled`

## Config

`voodbuilder.modules.layouts.enabled` / `VOODBUILDER_MODULE_LAYOUTS`

## Tests

- `tests/Modules/LayoutsModuleTest.php`
- Existing chrome layout Feature/Unit tests still green with module enabled

## Suite

`588` PHPUnit tests OK

## Remaining Phase 6 order

Pages → Dynamic Data → Components → Popups
