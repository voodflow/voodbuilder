# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.11.48] - 2026-10-07

### Changed

- Inside a VoodMedia gallery, the IMAGE panel edits vault caption / alt / credits via `mediaMetaUrl` (shared across galleries); no Grapes “Caption display”. Stronger gallery detection (`vmedia-gallery-block` / `data-vmedia-gallery-lightbox`).

## [1.11.47] - 2026-10-07

### Fixed

- IMAGE settings hide caption / caption-display when the photo is inside a VoodMedia gallery block (those controls only painted the editor; live pages use vault meta + gallery block settings). Selecting such an image clears editor-only caption chrome and shows an explanatory hint.

## [1.11.45] - 2026-10-07

### Fixed

- Published page JIT CSS omitted `--text-7xl` / `--text-8xl` / `--text-9xl` theme tokens, so utilities like `lg:text-7xl` worked in the editor but fell back to default heading size on the live site

## [1.11.43] - 2026-10-06

### Added

- Declarative `data-vb-field-type="status"` (✓ / ✕) for pricing/compare cells
- Table section item-count: row/column controls via `data-vb-table` / `data-vb-table-col`
- Seeded marketing pages + footer links: License Agreement, Legal (Trademark Notice)

### Changed

- Style panel surface paints persist as `#id` CSS only — saved HTML no longer keeps inline `background-*` / paint styles for live preview
- Background publish normalizer strips redundant author paint when published CSS already owns it
- Tailwind color swatches resolve theme tokens and opacity (`bg-vp-brand-1/10`)
- Layer visibility and responsive canvas polish for nested / section chrome

## [1.11.42] - 2026-10-06

### Fixed

- Previewing or restoring a page revision (and recovering an unsaved draft) froze the editor with no progress UI; the compile overlay now paints first, with elapsed time, before the canvas swap

## [1.11.41] - 2026-10-06

### Fixed

- FAQ accordion on already-saved pages stayed `max-w-3xl` inside the container; categories now fill the section measure (normal / full), and the editor strips that inner cap on load

## [1.11.40] - 2026-10-06

### Fixed

- Nested repeating items (FAQ question inside a category): selecting a question now opens Question fields in Content — `resolveFocusedItem` walks ancestor `data-vb-item` nodes so an outer categories list still resolves the parent category, then drills into the question
- Canvas toolbar Delete on a nested FAQ field removes the whole repeating item (question/category), not only the inner text node

### Changed

- FAQ accordion catalog layout uses full container width (header + categories stack); width follows section normal/full content-width

## [1.11.39] - 2026-10-01

### Changed

- Repeating section items (item count / Content panel) are **declarative only**: catalogs must ship `data-vb-items-root`, `data-vb-item`, and `data-vb-item-count`. Removed `SectionItemCountAnnotator` per-block PHP rules so third-party element libraries need no VoodBuilder changes.
- Nested FAQ categories/questions use the same declarative item-count UX as toolkit pills (select on canvas to edit).

## [1.11.38] - 2026-09-30

### Added

- Declarative Content fields support `data-vb-field-type="rich"` (aliases `richeditor` / `html`) via the light RTE, for repeating items such as FAQ answers
- Elements companion FAQ accordion (`vb-landing02-faq`): item count, Tabler icon picker (same as toolkit pills), question text, rich answer

### Changed

- Moved `vb-landing02-faq` from Community core to the Elements companion catalog; removed local `VoodbuilderLanding02Sections` registration and Landing 02 starter seed

## [1.11.37] - 2026-09-30

### Fixed

- Footer column menus render Tabler icons beside links again (list layout, no mega panel)
- Admin menu editor hides mega-menu description / dropdown layout fields on footer placements

## [1.11.34] - 2026-09-29

### Added

- **Site Visit** Voodflow trigger: start workflows when a visitor lands on a site page, menu item URL, or custom path (beacon + `POST /voodbuilder/visits`, payload includes `visitor_key` for Trigger Popup)

### Changed

- **Site Visit** (recorder + beacon + public endpoint) waits for vcookiebar **marketing** consent before setting `vpopups_vid` or queuing workflows

## [1.11.32] - 2026-09-29

### Added

- Editor Save shortcut: **Ctrl+S** (Windows/Linux) and **Cmd+S** (macOS), including while focus is in inspector fields or the canvas

## [1.11.31] - 2026-09-29

### Added

- Button / text link / icon / RTE link type **Popup** when vpopups has loaded popup targets — selecting a popup writes `data-vpopups-open="{id}"` (same bind as Copy bind)

## [1.11.30] - 2026-09-29

### Added

- Popup editor: pass `popupPresets` into the editor shell and register them via `registerPopupsUi({ popupMode: true })` (catalog owned by `voodflow/vpopups`)
- Blocks sidebar category order for localized “Popup templates” labels (pinned near the top in popup mode)

### Docs

- Documented dynamic-block styling limits (shell / anchored dropzones vs Blade-owned markup): [docs/dynamic-blocks.md](docs/dynamic-blocks.md)

### Fixed

- Theme (and other chrome) toggles no longer append `#` to the URL or scroll the page to the top: CTA annotation no longer morphs them into `<a href="#">`, public chrome render restores any already-morphed controls to `<button type="button">`, and the click handler calls `preventDefault`
- Canvas RTE: force-release contenteditable on outside pointer-down so other texts stay selectable without Save
- Page template apply: strip compiled utility sheets before `CssComposer.setStyle` to avoid multi-second Grapes parse lag
- Language switcher chips stay compact in nav dropdowns (5-column chip grid, not full-width menu rows)

## [1.11.29] - 2026-09-28

### Fixed

- Blocks no longer get pinned to dark: paste/import normalization stopped adding a `dark` class to pasted-component roots that contain `dark:` utilities (which every block gets once you style it in Dark). The forced class made the whole block use dark tokens (e.g. code blocks, text colors) and `dark:` variants even in light mode
- Existing pages are cleaned automatically on editor load, on Save and on public render (pages and site chrome)

### Tests

- `tailwind-plugin-defer` window stub now provides `setInterval` / `clearInterval` used by the build-status elapsed timer

## [1.11.28] - 2026-09-28

### Fixed

- Block dark background images now actually show in dark mode: dark companions (`html.dark #id`) are emitted with `!important` on Save, in the canvas preview and at publish time, so they beat the light inline `style=""` and legacy `#id { … !important }` paints — no re-save needed for existing pages

## [1.11.27] - 2026-09-28

### Fixed

- Save no longer strips block photos: the page-wallpaper cleanup only removes the wrapper (re-emitted) and orphan ids; blocks present in the page keep their light `#id` and dark `html.dark #id` images
- Light and dark block backgrounds are independent again: Clear in dark writes a dark-only `background-image: none` (kept through Save and reload) and never touches the light photo; Clear in light never touches the dark photo
- Dark paint paths no longer copy the light photo into dark; the Image field still shows an inherited light photo so it can be cleared for dark only
- Utility cascade normalization returns the original CSS when parsing fails instead of an empty sheet

## [1.11.26] - 2026-09-28

### Fixed

- Dark Style panel: element decoration photos that only live on light `#id` / `data-vb-style-bg-src` now show in the Image field (no empty input while the canvas still paints)
- Clear in dark removes that inherited light photo from `#id` when there is no dark companion — Clear no longer appears to do nothing

## [1.11.25] - 2026-09-28

### Fixed

- Public pricing columns: concatenated Tailwind sheets no longer leave `.w-full` after `@media .md:w-1/3` — publish reorders utilities so responsive widths win again
- Page wallpaper Clear/Save: every stale page-wallpaper `#id` (including stolen block rules) is stripped before Save re-emits only the current light+dark surface CSS — blocks no longer keep a light page photo in dark mode

## [1.11.24] - 2026-09-28

### Fixed

- Public page wallpaper: light mode no longer keeps the dark photo — publish no longer rewrites `html.dark #id` into the light `body::before` layer when duplicate dark companions exist
- Element dark-id hydrate no longer imports page-wallpaper layout leftovers from `html.dark #id { attachment:fixed }` rules

## [1.11.23] - 2026-09-28

### Fixed

- With a decoration photo, Background Color tints the image again: the photo-visibility scrim resolves the active Style theme Color (`dark:bg-*` included) instead of falling back to near-black `--color-vp-bg`

## [1.11.22] - 2026-09-28

### Fixed

- Element Decorations backgrounds (solid color / opacity, gradient, photo) now author independently for Light and Dark via `dark:` utilities, dual data attrs, and Save-emitted `html.dark #id` companions — light edits no longer stick when the top-bar theme is Dark

## [1.11.15] - 2026-09-26

### Fixed

- Sub-theme light palettes (`site` / `blog` / `news`) now use `:not(.dark)` so light `--color-vp-*` tokens never apply when the editor/canvas is in dark mode
- Canvas theme re-applies after companion script load and on `storage` theme changes

## [1.11.14] - 2026-09-26

### Fixed

- Editor canvas dark mode: companion ES modules no longer go through Grapes `canvas.scripts` (that blocked `renderBody` and left the iframe light while the chrome went dark). Scripts inject after body load; theme prefers live `html.dark` and re-syncs on host class changes / `frame:load:body`.

## [1.11.13] - 2026-09-26

### Fixed

- Dynamic companion blocks: mark intentional root deletes (`component:remove:before`) so child-remove storms (e.g. Core nodes grid) no longer cascade hundreds of microtasks and stall the editor

## [1.11.12] - 2026-09-26

### Added

- Editor canvas: `voodbuilder.editor.canvas_scripts` — companion ES modules injected into the Grapes iframe (`type=module`) so islands can read canvas `html.dark`

## [1.11.11] - 2026-09-26

### Fixed

- Theme safelist: `grid-cols-[repeat(auto-fit,minmax(…))]` utilities ship in the public theme so marketing/product grids stay multi-column on published pages (editor JIT already had them)

## [1.11.7] - 2026-09-26

### Fixed

- Hero CTA glow-pulse: Chrome no longer double-plays entrance (CTA transition + `vb-cta-rise`); nested on-visible markers inherit the section reveal

## [1.11.6] - 2026-09-26

### Changed

- Mobile mega menu (weDevs-inspired): icons + stacked titles/subtitles in the site drawer; fluid slide-in (0.4s) and accordion expand without `hidden` snap
- Docs/tuts reading drawer: smoother slide easing

## [1.11.5] - 2026-09-26

### Fixed

- Mobile nav: mega-menu descriptions no longer sit in a separate flex column beside the label — label and caption stack vertically inside the drawer

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
