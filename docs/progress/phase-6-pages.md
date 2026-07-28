# Phase 6 — Pages module

## Boundary

- `PagesModule` owns GrapesJS page save + form-submit routes and the `pages` content channel
- Filament `SitePageResource` gated via `modules.pages.enabled` ∧ `pages.enabled`
- Editor `canEdit` requires `PagesModule::isEnabled()`
- Public show routes remain in `routes/web.php` behind `pages.enabled` (Core)

## Config

`voodbuilder.modules.pages.enabled` / `VOODBUILDER_MODULE_PAGES`  
(also requires legacy `voodbuilder.pages.enabled`)

## Tests

- `tests/Modules/PagesModuleTest.php`
- Existing SitePage Feature tests still green with module enabled

## Remaining Phase 6 order

Dynamic Data → Components → Popups
