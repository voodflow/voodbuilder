# Phase 6 — Templates + Themes modules

## Templates

- `TemplatesModule` owns Editor page-template routes
- Editor URLs / sidebar gated when disabled
- Config: `voodbuilder.modules.templates.enabled`

## Themes

- `ThemesModule` owns Theme Studio Livewire + ThemeMap assets
- Built-in `SubThemeRegistry` remains Core (pages still resolve themes)
- Settings “Themes” tab hidden when module disabled
- Config: `voodbuilder.modules.themes.enabled`

## Tests

- `tests/Modules/TemplatesModuleTest.php`
- `tests/Modules/ThemesModuleTest.php`
- Existing template/theme Feature/Unit tests still pass with modules enabled

## Suite

`582` PHPUnit tests OK

## Remaining Phase 6 order

Menus → Layouts → Pages → Dynamic Data → Components → Popups
