# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

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
