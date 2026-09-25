# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.11.4] - 2026-09-26

### Fixed

- Integration tab selects: flat non-native styling on macOS (no glossy system chrome)
- Layout editor mobile preview: local nav Menu / “On this page” are interactive (drawer + details), matching the public VitePress pattern

### Changed

- Reading preview uses the same `data-vp-*` hooks as companion pages so canvas chrome runtime can drive the drawer

## [1.11.3] - 2026-09-26

### Fixed

- Integration typography now wins on docs/tutorials: unlayered `.vp-doc-title` / `.vp-doc h*` rules beat AppTypography’s `:where(h1)` (which previously kept site H1 sizes even when the layout set `xs`)
- Layout editor: footer blocks are placed by block id, not document order, so a footer no longer lands in the Header zone after reload

### Changed

- Integration type scale: removed the separate **Link** row — sidebar / TOC links follow **P**; H5 stays after H4

## [1.11.2] - 2026-09-26

### Changed

- Integration type scale: **H5** sits after H4 (before P); removed the “Side columns” heading and neutralised H5/Link labels (companion-agnostic)

## [1.11.1] - 2026-09-26

### Fixed

- Layout editor Save no longer short-circuits when only the Integration tab changed: the save fingerprint now includes reading typography, the tab marks the page unsaved, and draft restore reapplies those settings
- Integration tab copy clarifies companion-only scope (docs/tutorials), not Voodbuilder Site pages

## [1.11.0] - 2026-09-25

### Added

- VitePress-style mobile reading layout for companion plugins: below 960px the sidebar becomes an off-canvas drawer opened from a sticky local nav (“Menu”), below 1280px the “On this page” outline moves into a local-nav dropdown, and a prev/next pager closes every page (`<x-voodbuilder::reading-local-nav>`, `<x-voodbuilder::reading-pager>`)
- Layout Integration tab: **H5 · column titles** and **Column links** rows to tune sidebar / “On this page” titles and links per viewport
- Layout editor canvas previews the mobile/tablet reading layout (local nav, hidden columns, pager)

### Changed

- Column titles (sidebar groups, “On this page”) use the **Heading font**; column links keep the body font

### Fixed

- Mobile site menu: links were swallowed by the click handler and did not navigate on the public site

## [1.10.0] - 2026-09-25

### Added

- Settings → Site typography: **Type scale** preset (Compact / Standard / Large / Editorial), each a responsive H1–H4 + text scale (Mobile / Tablet ≥ 48rem / Desktop ≥ 64rem) with weights and line heights; the choice shows its px steps. Per-element tuning stays in the layout Integration tab
- Layout Integration tab: Mobile / Tablet / Desktop strip (synced with the canvas device) for per-viewport sizes; **Heading font** applied to companion headings via `--vp-font-family-doc-heading`

### Changed

- Layout Integration typography is now an override of site Settings: **Heading font** and **Body font** default to “From site settings”, every type-scale value can inherit, and Settings changes reach docs/tutorials without re-saving the layout
- One Integration configuration for every companion plugin: the separate sidebar type scale and the Secondary font were removed; “Body base size” merged into the P size
- Sidebar group titles and the “On this page” title are uppercase `<h5>` (0.75rem, 700, VitePress-style) in the body font, with `<a>` children at 0.875rem
- `--vp-app-*-size` / `--vp-doc-*-size` are emitted as responsive `<style>` rules instead of inline `<html>` styles

### Fixed

- Layout editor: dropping a Site nav or footer no longer inserts it twice (the footer render endpoint nested a second block root; near the canvas top the page-editor top-drop fallback inserted a copy on top of the layout zone insert)
- Saving a layout no longer turns an empty reading font into `inter`, which detached companion pages from the site fonts (migration resets those layouts to inherit)

### Upgrade

- Run `php artisan migrate` (adds `reading_heading_font`, drops the legacy reading font size / sidebar columns, resets `inter` reading fonts to inherit)
- Rebuild host assets (`npm run build`) and clear the settings cache (`php artisan cache:clear`): the old `typography_type_scale` setting is dropped and the site uses the **Standard** preset until another is chosen

## [1.9.0] - 2026-09-25

### Changed

- Site languages have a single source: `APP_LOCALES` / `APP_LOCALE` in the host `.env`, read as `config('app.locales')` / `config('app.default_locale')`. VoodBuilder no longer depends on `Voodflow\Vtuts\Support\Locales` and ignores package configs (`vtuts.locales`, `vdocs.locales`, `voodbuilder.editor.conditions.locales`, …)
- The primary site language is `APP_LOCALE`; the "Primary site language" setting was removed
- Icon link settings use the shared link target fields (Button, Text link, link picker); typing a route parameter keeps focus

### Upgrade

- Add to the host `config/app.php`: `'locales' => env('APP_LOCALES', env('APP_LOCALE', 'en'))` and `'default_locale' => env('APP_LOCALE', 'en')`, then set `APP_LOCALES=en,it` in `.env`

## [1.8.0] - 2026-09-25

### Fixed

- Reload keeps saved block settings: animated CTA, counter, logo scroll, gallery, anchor, reading time, logo grid/split, icon links and media hero traits hydrate from their saved HTML attributes instead of snapping back to type defaults; CTA `href` keeps the saved URL over a `#` default
- Selecting a CTA button no longer throws `e.get is not a function` in the TraitManager (link traits refresh as a real Traits collection)
- Cached menus keep mega-menu `description` and `dropdown_layout` on repeat requests

### Security

- Site chrome HTML (header/footer) passes through the HTML sanitizer before render; global text tags are escaped when replaced into HTML
- HTML sanitizer fails closed on unparsable input, allows `data:image` only on media elements and drops SVG `animate`/`set` that target URL attributes
- Remote template import: DNS resolved once and pinned (IPv4 + IPv6 checked against private ranges), redirects capped at 3 and re-validated
- Author CSS (saved and draft) can no longer break out of `<style>` via `</style` or `<!--`
- URL bindings on `<button>` render a real `<a role="button">` without form/script attributes

### Changed

- Render-time page CSS recompiles are cached per package version + content; menu trees load in one query
- Shared link target fields for Button, Text link and the link picker dialog; spacing sector split out of the Style panel
- Intentionally swallowed editor errors log through `debugSwallowed()` (enable with `localStorage.voodbuilderDebug = '1'`)
- CI: GitHub Actions workflow (Pint, PHPStan level 5 with baseline, PHPUnit, Vitest) and `composer lint|analyse|test|test:js` scripts
- 0.x release notes moved to `CHANGELOG-0.x.md`

## [1.7.3] - 2026-09-25

### Fixed

- Account menu off no longer hides theme / language behind a misleading user icon: guests get a preferences (sliders) trigger without login/register; enabling Account menu restores the user icon and auth links

### Added

- Navigation mega menus: optional item `description` (subtitle) and `dropdown_layout` (`auto` / `list` / `mega`), richer dropdown rows with optional Tabler icons, and auto mega grid when children are rich or ≥5
- Expanded menu Tabler icon set for product chrome (docs, media, pricing, privacy, etc.)
- Filament menu icon picker: searchable with live SVG preview, outline + filled variants (`name` / `name:filled`) from the full Tabler catalog

## [1.7.2] - 2026-09-25

### Changed

- Package health: security policy, Dependabot with update cooldown, pinned GitHub Actions, lean Composer dist (`export-ignore`)
- Laravel Pint added (`composer format`) and applied — formatting only, no behaviour change

## [1.7.1] - 2026-09-25

### Fixed

- Pasted Tailwind icons whose root is the SVG itself keep `currentColor`: SVG paint cleanup/bake runs before the layout shell hoists the SVG's `text-*` classes onto the wrapper section (no more baked black fill)

## [1.7.0] - 2026-09-25

First stable release line; rolls up 0.1.167 – 0.1.169 (dual light/dark page wallpaper, theme-aware Style panel, Code block layout).

### Fixed

- Dual page wallpaper: Save keeps light `#id` and dark `html.dark #id` as separate rules (Grapes no longer merges dark into light via `#id, html.dark`); `FontStylesheets` no longer collapses the dark companion into the light rule, so the public page switches photo with the theme
- Editor canvas preview shows the right wallpaper in both themes and refreshes on Light/Dark toggle: reads the saved author CSS (Grapes `getCss()` drops `html.dark #id`), light reads ignore `html.dark` rules, preview style stays last in the iframe head and the Grapes wrapper is transparent

## 0.x

Pre-1.0 releases (0.0.1 – 0.1.169) are archived in [CHANGELOG-0.x.md](CHANGELOG-0.x.md).
