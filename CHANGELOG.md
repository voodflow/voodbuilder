# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Content channel **Search** (`search`) for Theme Map / Layouts; inherits Documentation chrome by default; excluded from search filter pills
- Search results page uses the **doc layout** (narrow column, like documentation articles)
- `SearchExcerpt` helper — plain-text snippets without raw Markdown (`##`, `**`, links, …)
- Settings → **Search**: results per page, per channel, snippet length (with config fallbacks)
- Search ranking (title / meta / body), match-centered snippets, and `<mark>` highlights
- Search pagination with deep-linkable `?page=` (keeps `q` / `type`)
- Header search palette: live top matches (Filament-style), keyboard nav, recent searches, link to full results
- Developer docs: PHP SDK [Site search](docs/manual/developer/php-sdk/site-search.md) — how companions make content searchable
- Doc reading typography closer to VitePress (`.vp-doc` sizes/spacing, 43rem content width, sidebar weights)
- **Layouts → Reading typography**: FontCatalog family + base size (`sm`–`xl`, default 17px) for docs/tutorials via `--vp-font-family-doc` / `--vp-font-size-doc`
- Site Page admin **SEO** section (meta title, description, robots, canonical) on the morph `seo` relation
- Site Page JSON-LD breadcrumbs (and Article schema for section articles)
- Dynamic templates prefer bound entity SEO (event, exhibitor, …) over the template page title

### Changed

- Documentation search hits show **Topic › Section** as meta, plus a short content excerpt
- Search results UI: card list, softer filters, cleaner hierarchy
- Search filter pills show result counts; empty channels are omitted
- “All” results are a single ranked list with channel headers when the channel changes
- Doc layout: content+TOC as `w-fit` pack beside the sidebar (`gap-10`); article uses `--width-vp-content` without filling leftover viewport

### Fixed

- With empty page `route_prefix` + `menu_paths`, reserve `docs` / `tutorials` (and `vdocs`/`vtuts` config prefixes) so site pages no longer shadow companion public routes (`/tutorials/…` → 404)
- Search `<mark>` contrast: readable ink on brand tint in light and dark (no inherited light-on-yellow)
- Search pagination: out-of-range `?page=` is clamped so a new query never shows an empty list while the count is > 0
- Public layouts emit a single `<title>` via `{!! seo() !!}` (removed duplicate Blade title)
- Site Page meta description no longer falls back to stripped canvas HTML; uses excerpt → SEO override → site default
- Gated / password-protected pages default to `noindex, nofollow` unless SEO robots is set
## [0.1.46] - 2026-09-10

### Added

- Search content channel for chrome layout assignment (inherits Documentation unless Search is set)

### Changed

- Site search results use the Documentation layout chrome; channel filter excludes the Search surface itself

## [0.1.45] - 2026-09-10

### Fixed

- Popup code-import CSS: keep content gutters (do not zero `.px-8` on copy stacks)

## [0.1.44] - 2026-09-10

### Added

- Popup editor: content-width toolbar (full ↔ normal 24rem, centered) — including Layout Div / max-w columns
- Media-hero registry includes Elements `vb-hero-cinematic` (same vmedia / Background path as `vb-bg-image`)

### Fixed

- Legacy Docs auto-inject (header + mobile) lists navigable product topics like the Documentation menu item type
- Remote Hero · cinematic full-bleed was not wired as a media hero (no Background / vmedia picker)

## [0.1.43] - 2026-09-10

### Added

- Admin license dashboard API: `GET|POST /voodbuilder/admin/license/{status,refresh}` (`schema_version: 1`) for a future Filament licenses dashboard plugin
- `CatalogCredentialResolver` — prefers AnyStack `catalog_credentials` from the entitlements snapshot, falls back to env CDN token
- Docs: `docs/ANYSTACK_SETUP.md` (endpoint contract, catalog credentials, distribution map)

### Changed

- Library SOURCE catalogs: Elements and/or Templates companions only (Components stay in the left sidebar)

### Fixed

- Condition `page_path contains /` matches the homepage only (before it matched every URL because `/` is a substring of all paths)

## [0.1.40] - 2026-09-09

### Security

- Page template remote catalog/install sends `X-VoodBuilder-Catalog-Token` and rewrites absolute CDN `bundle_url` values to relative paths for the browser

### Changed

- Default `page_templates.catalog_url` → `https://api.voodflow.com/voodbuilder/templates/page-catalog.json`

## [0.1.39] - 2026-09-09

### Added

- Product landing page template kit (`voodflow-builder-product-landing.json`) for Templates import
- Page template apply: canvas build overlay labels (“Applying template…” / “Compiling styles…”)

### Fixed

- Empty chrome-shell pages (drop spacers only) no longer prompt Replace/Append when applying a template
- Canvas compile overlay also covers template-apply builds, not only page/component CSS scopes

### Changed

- Landing CTA buttons stay compact pills on mobile (`w-fit`, no full-width stack)

## [0.1.38] - 2026-09-09

### Changed

- Library remote catalog label: **Elements** (correct naming: Elements = provided library; Components = user; Templates = user + marketplace)

## [0.1.37] - 2026-09-09

### Changed

- Library remote catalog label: **Components** (was Wireframes); bundle id `wireframes` unchanged for API URLs

## [0.1.36] - 2026-09-09

### Fixed

- Page template apply/append: force Tailwind JIT after replace-or-append so styles compile in the canvas without waiting for Save
- Page CSS schedule no longer drops rebuilds while bulk structure updates / CSS suspend are active

### Changed

- Emit `voodbuilder:active-library-changed` when switching Elements / Components / Templates sidebar tabs (for companion Library UI)

## [0.1.35] - 2026-09-09

### Changed

- Filament navigation group label is **VoodBuilder** (was `Voodbuilder`)

## [0.1.34] - 2026-09-09

### Changed

- Align with VoodMedia plugin-owned vault registry: register Builder root via `RegistersPluginVault`
- Asset Manager uses only `vmedia.media.*` routes (drop transitional `voodbuilder.editor.*` media aliases)

## [0.1.33] - 2026-09-09

### Fixed

- Stop resurrecting deleted component content: empty instance shells are no longer refilled from the catalog on save/reload (hydrate/ensure)

## [0.1.32] - 2026-09-09

### Fixed

- Editor refresh: stop false "Could not load the block library" notice caused by `hydrateComponentInstance` referencing undefined `editor` during component catalog sync; isolate post-fetch failures in `loadBlocks`

## [0.1.31] - 2026-09-09

### Fixed

- Import-from-code: visible HTML/CSS editors (fallback textareas + contrast) and alphabetical component categories
- Do not invent "Button"/"Send" labels on empty or icon-only `<button>` markup (annotator, Grapes forms button, CTA morph)

## [0.1.30] - 2026-09-09

### Fixed

- Components import/export: companion plugin unlocks APIs (edition matrix is soft upsell only); optional Shield abilities when defined
- Page editor chrome (header/footer): no hover outline — layout editor only

## [0.1.29] - 2026-09-09

### Fixed

- Reading progress pins under the visible site header, or flush to the viewport top when the nav scrolls away (no more floating gap with a non-sticky menu)

## [0.1.28] - 2026-09-09

### Added

- Social share: configurable Share title and Email subject (mailto subject); empty falls back to page title; email body includes title + URL

## [0.1.27] - 2026-09-09

### Fixed

- Deleting Social share (or any child) inside Hero · background image/video no longer removes the entire hero — media heroes are excluded from dynamic companion cascade-delete and from `voodbuilder-dynamic` parse steal

## [0.1.26] - 2026-09-09

### Fixed

- Social share round + icon-only chips stay circular (no stretched ovals); labels hide in icon mode
- Copy/share controls no longer show Grapes “Button” text — all items are share links under the Social share root
- Selecting social share inside a media hero no longer promotes delete/selection to the whole Hero block

## [0.1.25] - 2026-09-09

### Added

- Social share Content settings: enable/disable networks, square/round shape, brand/transparent/custom color, icon+text / icon / text

### Fixed

- Social share buttons no longer open link/CTA editor — share URLs stay fixed per network

## [0.1.24] - 2026-09-09

### Fixed

- Reading progress on marketing pages no longer jumps 0→100%: ignore bare `<article>` cards and use full-page scroll for the Utilities progress block

## [0.1.23] - 2026-09-08

### Fixed

- Reading progress thickness actually applies (bar was capped at 2px by nav progress CSS)

### Changed

- Reading progress color picker: Brand tokens + full Tailwind palette (slate-50 … rose-950); thickness options up to 12px

## [0.1.22] - 2026-09-08

### Added

- Reading progress Content settings: color (Brand / Brand 2 / Light / Dark) and thickness (2–6px)

### Fixed

- Reading progress advances on marketing pages without an article root (page scroll); standalone tracks stay visible under the sticky nav

## [0.1.21] - 2026-09-08

### Fixed

- Reading progress stays fixed under the sticky nav on published pages (no more scrolling away); editor-only relative positioning is no longer saved as inline styles

## [0.1.20] - 2026-09-08

### Fixed

- Page content orphan purge no longer deletes Utilities / Basic / Media dropped on the home page root (reading progress, reading time, social share, headings, …) right after drag-stop

## [0.1.19] - 2026-09-08

### Changed

- Sidebar category **Single** renamed to **Utilities** (alias kept for older labels)
- With Elements companion active, the left accordion keeps foundation tiles only (Layout / Basic / Media / Utilities / Site); section templates live in the Library modal
- Elements soft upsell shows when the companion is missing, even if Pro capability is present

### Fixed

- Reading time, reading progress, and social share utilities render in the editor canvas (runtime targets the canvas frame; progress bar no longer stuck at `width:0` / missing brand fill)
- Standalone progress / reading-time / social-share styles ship in `theme.css` and canvas `section-utilities.css`

## [0.1.18] - 2026-09-08

### Fixed

- Hero · background image: Background settings (Choose static photo) stay available after a dynamic image bind; choosing a library image clears the live-data binding

## [0.1.17] - 2026-09-08

### Fixed

- Dynamic binding Field list for Hero · background image: image fields (e.g. featured image) appear when the section or media layer is selected, not only when the inner `<img>` is selected; bind retargets to the hero media image and keeps `data-vb-bg-src` in sync

## [0.1.16] - 2026-09-08

### Fixed

- Content channel matching: patterns like `vtuts.*` / `vdocs.*` (score `-1`) no longer fail to match — tutorials/docs now resolve their chrome layout instead of falling back to the classic app shell nav

## [0.1.15] - 2026-09-07

### Fixed

- Layout editor: Templates tab stays hidden and page templates (Landing 01/02, …) are never registered or visible — templates remain page-editor only

## [0.1.14] - 2026-09-07

### Fixed

- Media heroes (`vb-bg-image` / `vb-bg-video`) keep expandable Layers children — chrome-shell lock no longer flattens their tree
- Background image Content settings include Minimum height (50/70/100vh); height is re-applied after page CSS compile
- Clicking hero copy/content no longer opens Background settings (only media layer / section root)

## [0.1.13] - 2026-09-07

### Fixed

- Media heroes promote selection to the section only when clicking the background media layer; headings, copy, and CTAs in the content stack stay selectable
- Content panel no longer steals Rich Text clicks inside media heroes to open Background settings

## [0.1.12] - 2026-09-07

### Fixed

- Canvas selection no longer jumps to the whole section for every catalog block — only media heroes (`vb-bg-image` / `vb-bg-video`) still promote to the root so Background settings open; blog cards and similar keep the clicked image/element

## [0.1.11] - 2026-09-07

### Fixed

- Editor media browser refreshes the upload album when browsing a folder, then opens that album after upload
- Published sticky/fixed SITE nav: page `main` gets top padding so the first block starts below the bar (not under it)

## [0.1.10] - 2026-09-07

### Changed

- Layout editor: hide Templates tab (page templates belong on pages, not chrome)
- Layout editor sidebar: enforce SITE chrome allowlist in JS (no LAYOUT / Basic tiles)

### Fixed

- Hero / section Tailwind compiles again on drop (not only after Save) — drag-lock no longer drops pending JIT
- Sticky/fixed SITE nav reserves height when spacer class is missing (editor + published chrome shells)

## [0.1.9] - 2026-09-07

### Changed

- Layout editor sidebar: SITE chrome only (nav/footer); no Page content tile, no landing sections
- Fresh seed menus start empty (no default Home link)
- Layout editor Dynamics tab soft-gates like the page editor when the companion is missing

### Fixed

- Sticky SITE nav reserves space on the published site (first block no longer hidden under the bar)
- Hero background image click opens Background image settings without hunting Layers


## [0.1.8] - 2026-09-07

### Fixed

- Layout / page visual editor 403 on Shield-less Filament installs: `AdminAccess` no longer requires an active Filament panel request (editor runs outside `/admin`)

## [0.1.7] - 2026-09-07

### Changed

- Fresh install no longer seeds sample pages (home / privacy / cookie); opt in with `VOODBUILDER_SEED_SAMPLE_PAGES=true`
- Empty public home guides users to create a Layout first, then a Page (translatable EN/IT); Blade nav/footer hidden on that screen

### Fixed

- Home title seeder used a missing translation key (`voodbuilder::home.page_title`)

## [0.1.6] - 2026-09-07

### Fixed

- Install patches `site-runtime.js` into `vite.config.js` (public `@vite` no longer 500s after a successful build)
- Public layout skips Vite entries missing from the manifest instead of throwing


## [0.1.5] - 2026-09-07

### Fixed

- Admin Pages / Navigation / Layouts visible on Filament installs without Shield (use current panel for access checks)
- Install / sync-npm now adds CodeMirror + js-beautify packages required by the editor build


## [0.0.11] - 2026-07-23

### Fixed

- Admin database notifications bell: extend Filament panel `DatabaseNotifications` Livewire component so the topbar trigger renders correctly

## [0.0.10] - 2026-06-10

### Added

- `ProductPromoBlock` rich content block for product call-to-action banners
- `PackagePromosBlock` for a three-column grid of package CTAs (Vdocs, Vtuts, Voodflow)
- `voodbuilder.packages.voodflow_url` and default home promo for **Voodflow**
- Filament settings: tabbed UI (Site, Appearance, Theme, SEO, GEO & AI, Analytics)
- Per sub-theme brand color overrides via `ThemePalette` and `theme-vars` component
- `ThemePaletteTest` unit test

### Changed

- Default home focuses on **Voodbuilder** capabilities: six feature cards plus optional companion plugins
- Hero headline no longer references VitePress; killer **import full VitePress site** feature is highlighted in the features grid (with Vdocs)
- Hero CTAs link to the Voodbuilder Filament plugin page and GitHub repository
- Default home CTAs for **Vtuts** and **Vdocs** point to Filament plugin marketplace pages
- Reading progress bar on doc/tutorial and sub-theme article shells; hidden when content is not scrollable
- Doc/tutorial header: no divider under nav; blog keeps divider without progress; news keeps progress without divider
- Outline links use `data-toc-link`; active styles scoped to `nav.vp-outline`

### Fixed

- TOC scroll-spy: single handler, correct `--spacing-vp-doc-offset`, no scroll-lock jump on smooth scroll
- `body_class` yield in app layout (progress bar on blog Ink shell)
- Sticky/fixed nav coordination with reading progress track

[0.0.10]: https://github.com/voodflow/voodbuilder/releases/tag/0.0.10

## [0.0.9] - 2026-06-09

### Changed

- README: document site page creation, routing (`/` vs `/pages/{slug}`), layouts, publishing, and SEO
- README: document navigation menus in detail (placements, item types, active route patterns, examples)

[0.0.9]: https://github.com/voodflow/voodbuilder/releases/tag/0.0.9

## [0.0.8] - 2026-06-09

### Added

- `MenuRouteCatalog` for Filament navigation menus: searchable select of public GET routes with automatic `route_match` patterns
- Configurable `menus.route_exclude_patterns` to hide admin and internal routes from the menu builder
- Rich editor **styled previews** for custom content blocks (`RichContentBlockPreview`, `rich-editor-block-previews.css`)
- `voodbuilder.packages.vtuts_url` and `voodbuilder.packages.vdocs_url` for default home CTAs

### Changed

- Default seeded home page promotes **Voodbuilder** with CTAs to vtuts and vdocs companion packages
- Custom blocks use `getPreviewLabel()` and wrapped preview HTML in the editor

### Fixed

- TOC scroll-spy and outline click navigation (correct active heading, scroll offset)
- Pointer cursor on frontend links and interactive elements

[0.0.8]: https://github.com/voodflow/voodbuilder/releases/tag/0.0.8

## [0.0.7] - 2026-06-08

### Fixed

- Load `filament-cookie-consent` translations when the package is excluded from Laravel auto-discovery (Filament settings labels no longer show raw translation keys)

## [0.0.6] - 2026-06-08

### Fixed

- `voodbuilder:install` always runs with the English locale so Artisan output and default seeded content stay in English regardless of the host OS or app locale
- Homepage `latest_vtuts` block uses `vtuts::vtut-card`

## [0.0.5] - 2026-06-08

### Fixed

- `SitePage::getUrl()` no longer throws when the `home` route is missing (uses `VoodbuilderUrls::home()`)
- `voodbuilder:install` removes Laravel’s default welcome route so voodbuilder owns `/` and the public theme loads
- vtuts locale fallback URL uses `VoodbuilderUrls::home()` instead of a hardcoded `route('home')`

## [0.0.4] - 2026-06-08

### Fixed

- `voodbuilder:install` post-install instructions are in English again
- Theme CSS path resolves automatically for Composer installs (`vendor/...`) and monorepo path repos (`packages/...`)
- `voodbuilder:install` patches `vite.config.js` when the theme entry is missing or still uses the legacy `packages/` path
- Doc outline scroll-spy handles duplicate heading links and bottom-of-page active state
- Default home CTA uses the configured vtuts URL prefix instead of a hardcoded `/vtuts` path

### Changed

- `config/voodbuilder.php` uses `VoodbuilderPaths::defaultViteEntries()` so `@vite` always matches the install location

## [0.0.2] - 2026-06-06

### Added

- Primary site language setting in Filament (no locale prefix for the primary language)
- Optional mobile logo upload in site settings
- Profile dropdown menu with language, theme, and account actions
- English and Italian strings for nav and settings helpers

### Changed

- Mobile navigation drawer with in-panel close control and improved scroll lock
- Sticky right-hand doc aside aligned with VitePress behaviour
- Account, register, and account settings pages migrated to Tailwind (`vp-*` tokens)
- Language switcher section hidden when the switcher is disabled in settings
- Search modal no longer locks page scroll; logo sizing tuned for desktop and mobile
- Home routes skip legacy `/{locale}` prefixes when tutorials use slug-based locales

### Removed

- Legacy “More” overflow menu in the header (replaced by profile menu)

[0.0.5]: https://github.com/voodflow/voodbuilder/releases/tag/0.0.5

[0.0.4]: https://github.com/voodflow/voodbuilder/releases/tag/0.0.4

[0.0.2]: https://github.com/voodflow/voodbuilder/releases/tag/0.0.2

## [0.0.1] - 2026-06-04

### Added

- Initial public release
- VitePress-inspired theme with mobile hamburger navigation
- Filament site pages, navigation menus, and settings
- Light/dark theme with admin defaults and optional user toggle
- SEO, favicon, logo, and social sharing defaults
- Cookie consent integration
- Account page with avatar support
- Comment notification bell (frontend + Filament)
- Extensible RichEditor custom blocks

[0.0.1]: https://github.com/voodflow/voodbuilder/releases/tag/0.0.1
