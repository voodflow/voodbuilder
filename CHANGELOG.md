# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [0.1.124] - 2026-09-23

### Fixed

- Background Color **opacity** actually paints: Grapes cannot store `bg-black/60`, so opacity uses `data-vb-bg-color-opacity` + inline `rgba`/`#id` while keeping plain `bg-*`

## [0.1.123] - 2026-09-23

### Added

- Background **Color opacity** (Tailwind v4 `bg-black/50`) so solid colors can reveal a parent background image
- Sync / migrate legacy `bg-opacity-*` from imported components into slash opacity and Style panel fields

## [0.1.122] - 2026-09-23

### Fixed

- Gradient + Image: Photo visibility now softens gradient stop alpha so the photo stays visible (opaque TW `from-*`/`to-*` would fully cover the url)

## [0.1.121] - 2026-09-23

### Fixed

- Background **Gradient** visible again with Tailwind v4 (`linear-gradient(var(--tw-gradient-stops))` — do not prefix direction)
- After clearing a photo, author `background-image` is fully released so gradient utilities can paint (no leftover inline/#id block)

## [0.1.120] - 2026-09-23

### Fixed

- **Critical:** setting `background-color: transparent` (anti-flash under photo) no longer family-wipes `background-image` — photos survive refresh
- Hydrate decoration photos from `data-vb-style-bg-src` / CssComposer / live CSS on editor load and dynamic remount

## [0.1.119] - 2026-09-23

### Changed

- Background Image paints at **100%**; Color/Gradient sit **above** as overlays (Photo visibility = how much photo shows through the tint)
- With a photo active, solid `background-color` is forced transparent so the frontend no longer flashes full Color before the image loads

## [0.1.118] - 2026-09-23

### Fixed

- Background Image field keeps a durable reference (`data-vb-style-bg-src` preferred over composed CSS) so the thumbnail survives refresh
- **Clear** removes the photo from CssComposer `#id`, live page CSS, and the canvas DOM (no ghost image under a new Choose)
- Canvas live CSS no longer injects author `#id` paints (fixes Gradient hidden under stale `#id{background-image}`)
- After Clear, gradient utilities can paint again without the old photo underneath

## [0.1.117] - 2026-09-23

### Changed

- Background **Gradient** and **Image** can be used together (stacked layers); Color and Gradient remain mutually exclusive. Changing gradient no longer wipes the photo.

## [0.1.116] - 2026-09-23

### Fixed

- Changing Background Color no longer wipes the decoration photo (clearing `background-color` no longer family-clears `background-image`); color changes rebuild the opacity fade over the new swatch
- Persist `data-vb-style-bg-src` so Image field / fade survive reload and remount

## [0.1.115] - 2026-09-23

### Fixed

- Style inspector re-reads effective paints after refresh: Background Image (and other #id CSS) rehydrate into the right column on select / after dynamic remount

## [0.1.114] - 2026-09-23

### Fixed

- Published dynamic blocks keep the Grapes root `id` (and author background style), so Style panel `background-image` rules like `#id{…}` render on the frontend

### Added

- Style → Background → Image **opacity** control: fades the background image toward the solid background color underneath

## [0.1.112] - 2026-09-23

### Fixed

- Keep page styles after Anchor drop (union previous live utilities on JIT rebuild)

## [0.1.113] - 2026-09-23

### Fixed

- Hash / Anchor links no longer stop far above the target: site `scroll-padding-top` clears only the sticky nav, and `.vb-anchor` no longer doubles the docs offset

## [0.1.111] - 2026-09-23

### Fixed

- Button / link **Menu item** picker now lists nested items and dynamic children (e.g. Docs → VoodBuilder from the vdocs menu type), with labels like `Main navigation · Docs · VoodBuilder`

## [0.1.108] - 2026-09-23

### Added

- Button / text link / icon / RTE link types: **Email** (address + subject) and **App route** (select from MenuRouteCatalog, with required params)

## [0.1.107] - 2026-09-23

### Fixed

- Library / BlockManager inserts now multi-pass JIT-compile page CSS and hydrate CTA buttons immediately (Pricing no longer stays unstyled / non-linkable until Save)

## [0.1.106] - 2026-09-23

### Fixed

- Dynamic blocks (e.g. Voodflow · Core nodes) no longer vanish right after drop: cascade-delete on `component:remove` skipped during live refresh remount and when the block still has children

## [0.1.105] - 2026-09-23

### Fixed

- Pricing / Tailblocks CTAs with decorative SVG (e.g. "Submit" + arrow) now promote to smart link buttons with URL settings
- Billing toggles (Monthly/Annually) and slider chevrons stay native buttons

## [0.1.104] - 2026-09-23

### Fixed

- Component instances already on a page are snapshots: editing a library component no longer replaces canvas HTML (hides / per-page Explore labels stayed until catalog re-push restored defaults)

## [0.1.103] - 2026-09-23

### Fixed

- Utilities **Anchor**: Content inspector now shows an **Anchor ID** field (and opens the Content tab on select) instead of relying on hidden Grapes traits

## [0.1.102] - 2026-09-23

### Added

- Utilities **Anchor** block (Community foundation): invisible scroll target with configurable HTML `id`; link buttons/menus to `#id` — site theme already uses `scroll-behavior: smooth`

## [0.1.101] - 2026-09-23

### Fixed

- Applying a page template that already includes compiled CSS no longer re-runs Node `compile-css` (removes the multi-second “Compiling styles…” wait); empty-css starters still JIT as before

## [0.1.100] - 2026-09-23

### Fixed

- Templates library dark mode: card labels and chrome used undefined `--color-vp-c-text-*` (hard dark fallback) instead of editor `--color-vp-text-*`, so names were unreadable on dark cards

## [0.1.99] - 2026-09-23

### Changed

- Save JSON ships author CSS only (Style Manager / `#id`); server always recompiles Tailwind utilities and stores large sheets as a public-disk artifact (`css_artifact`) so shared hosts do not need raised `post_max_size` / Laravel `max_css` for megabyte stylesheets
- Published pages with a CSS artifact load it via `<link>` instead of inlining megabytes in `<style>`
- Revisions / autosaves slim oversized inline CSS into versioned artifact files

## [0.1.98] - 2026-09-22

### Fixed

- Save no longer always spawns Node Tailwind JIT: reuses the smart page CSS resolve when the editor live sheet already covers utilities (keeps Save fast after class/structure tweaks)
- Component live autobuild skips compile-css when new class tokens are already theme-covered (stops “Compiling styles…” storms that blocked Save)
- Page save payload prefers live JIT utilities + Style Manager rules over full Grapes `getCss()` utility dumps

## [0.1.97] - 2026-09-22

### Fixed

- Paste / code-import: plain `<section class="bg-… max-w-…">` snippets are promoted to the editor section (no nested section-in-shell), measure utilities (`max-w-*`, `mx-auto`) are stripped from the full-bleed root, and surface utilities on a root `div` (bg, padding, overflow, text color) lift onto the section so selection/save no longer shrink the band

## [0.1.96] - 2026-09-22

### Fixed

- Pasted hero aurora / shade layers (`.vb-hero-aurora__mesh`, `role=shade`) are no longer stamped as Content shells with `max-w-[80rem]` / `mx-auto`; Save no longer re-applies that measure on decorative layers

## [0.1.95] - 2026-09-22

### Fixed

- Button/link Content settings: switching Link type URL → Site page no longer blanks the right panel (~2s TraitManager remount); page selection confirms without a redundant CTA morph

## [0.1.94] - 2026-09-22

### Fixed

- Layout editor refresh keeps author classes on chrome menu slots (e.g. `uppercase` on `[data-voodbuilder-desktop-nav]`) when Blade remounts site nav blocks
- Chrome layout save/load: hoist footer and reading-progress nested inside a nav block before Blade remount so they are not wiped
- Search palette: light-mode footer/kbd contrast inside dark site header; always show “View all results”; Enter opens the full results page
- Search results page uses layout width (not reading measure); result cards use theme divider borders that stay visible in dark mode

## [0.1.93] - 2026-09-22

### Added

- Menu tree: **Add sub-item** (+) on top-level rows and **Move to top level** on nested rows (less reliance on precise drag nesting)

### Changed

- Menu tree drag: narrower nest band (~30% centre) so same-level reorder is the default; parents with children stay expanded after rebuild so nested items do not look “missing”

## [0.1.92] - 2026-09-22

### Fixed

- Navigation menu tree drag-reorder no longer crashes when a registered type (e.g. vdocs `docs`) sits next to built-in enum types — Livewire EnumSynth metadata was index-bound; tree/form state now always exposes `type` as a string

## [0.1.91] - 2026-09-21

### Fixed

- Licence fail-open: keep last Agency/Developer snapshot when the API is down, when the licence key is temporarily unreadable, or when a flaky live reply reports Community while still active; memoize snapshot per request

## [0.1.90] - 2026-09-21

### Fixed

- Packagist latest version: pick highest stable tag and refresh stale cache when local is ahead (no false “Ahead of Packagist” after a new release)

## [0.1.89] - 2026-09-21

### Changed

- Edition modal tables share fixed column widths (Package / Active / Registered / Version) and tighter spacing; taller panel to reduce scroll

## [0.1.88] - 2026-09-21

### Changed

- Edition modal: Core + Agency/Developer companion inventory (active / panel-registered / version) + Extra core & licences (Vmedia, Vcookiebar); Community shows only Core + extras; meta summary in columns; no vdocs/vtuts

## [0.1.87] - 2026-09-21

### Changed

- Edition modal groups packages as Core / Edition / Companions / Other Voodflow plugins; meta summary in columns; affiliation label instead of Source
- Core `voodflow/voodbuilder` version checks Packagist; Agency/Developer listed under Edition (Community when neither is installed)

### Fixed

- Site footer layout in the editor canvas when `lg:*` utilities were missing from chrome-block-utilities

## [0.1.86] - 2026-09-21

### Changed

- Edition info opens as a centered modal (blurred dark backdrop) with installed package inventory: local/remote versions, Packagist vs Anystack source, and companion active state

### Fixed

- Core package status still reports ahead/update/current against api.voodflow.com; companions without a remote feed show local-only

## [0.1.85] - 2026-09-21

### Fixed

- Layout editor boot crash (`tablerIcon is not defined`) when vdocs/vtuts register Reading/Integration previews — import missing from the reset control

## [0.1.84] - 2026-09-21

### Fixed

- Popup page-path targeting uses the visitor page (query/`Referer`), not the AJAX `/popups/data` path — homepage `/` targeting works again

## [0.1.83] - 2026-09-21

### Fixed

- Popup Settings accordion titles match Style sector size (`0.625rem` / weight 700)

## [0.1.82] - 2026-09-21

### Changed

- Popup Settings typography/spacing aligned with Style sectors (lighter field labels, denser gaps, matching title scale)

## [0.1.81] - 2026-09-21

### Changed

- Popup Settings accordion matches Style sectors (grey header, caret, white body card)

## [0.1.80] - 2026-09-21

### Fixed

- Popup Settings save with the main editor Save (no separate Save & apply in the inspector)
- Custom select lists keep optgroup headers (page path targeting matches the modal)
- Airier popup Settings padding and reset control

## [0.1.79] - 2026-09-21

### Fixed

- Popup Settings inspector: flat Reading-typography layout (no nested cards, airy padding, uppercase labels; beats global form-field row styles)

## [0.1.78] - 2026-09-21

### Fixed

- Popup inspector settings stack vertically in the narrow sidebar (no modal 3-column General grid)

## [0.1.77] - 2026-09-21

### Added

- Popup edit mode: dedicated Settings inspector tab (cogs, last, preselected) for popup rules
- Compact accordion UI styles for the narrow popup settings sidebar

### Changed

- Content tab is block/traits only again (hero image settings stay reachable on select)
- README Editions: seat and unlimited-sites licence notes

## [0.1.76] - 2026-09-21

### Fixed

- Mount popup canvas settings as soon as the editor shell boots (not only on Grapes `load`)
- Aurora mesh `z-index` so catalog shade layers stay above section backgrounds

## [0.1.75] - 2026-09-21

### Added

- Popup edit mode: Content inspector mount for companion popup settings; omit Conditions tab

### Changed

- Wire `registerPopupCanvasSettings` when `popupMode` is active

## [0.1.74] - 2026-09-21

### Changed

- Treat `voodflow/vcookiebar` as a hard companion in install docs, settings copy, and Testbench providers (matches Composer `require`)

## [0.1.73] - 2026-09-21

### Fixed

- Public popup runtime resolves when `vpopups` is nested under `voodbuilder-agency` (Agency Composer install). Previously the Vite glob only looked at `vendor/voodflow/vpopups`, so `initPopups` was a no-op and published popups never appeared on the front.

## [0.1.72] - 2026-09-21

### Added

- README **Editions** section (Community / Developer / Agency) with Anystack checkout links

### Changed

- Page Save extracted for reuse by companion UIs (popups save the host page before Design / create)

## [0.1.71] - 2026-09-21

### Added

- Editor edition chrome: splash + topbar badge (Community / Developer / Agency) from the same AnyStack entitlements snapshot
- Edition info popover (licence status, expiry, docs) and package update status vs Anystack (`Current` / update available)
- Portal client for `GET /v1/packages/voodbuilder/latest` on api.voodflow.com

### Changed

- Topbar brand layout: icon | page name | edition (wider brand column)

## [0.1.70] - 2026-09-18

### Fixed

- Library upsell race: mount companion plugins after editor layout so Elements can clear the soft upsell; skip upsell when Library catalogs/dock are already present

## [0.1.69] - 2026-09-18

### Fixed

- Discover Popups editor UI inside `voodbuilder-agency` nests (same `@voodbuilder-editor` pattern as Elements)

## [0.1.68] - 2026-09-18

### Fixed

- Vite alias `@voodbuilder-editor` so companion JS resolves inside Developer/Agency bundles (Elements Library button)

## [0.1.67] - 2026-09-18

### Fixed

- Discover companion editor plugins nested inside `voodbuilder-developer` / `voodbuilder-agency` Composer bundles (Library button and other companion JS)

## [0.1.66] - 2026-09-18

### Fixed

- Licence key is read from Composer `auth.json` / `COMPOSER_AUTH` inside VoodBuilder core (no dependency on `voodflow/voodflow`)

## [0.1.65] - 2026-09-18

### Changed

- Maintenance release

## [0.1.64] - 2026-09-17

### Fixed

- Reading progress track uses a soft mix of the progress color (not `bg-vp-divider` / white in light mode)

## [0.1.63] - 2026-09-17

### Fixed

- Reading progress sits under the site header stacking context (`z-index: 20`) so profile/nav dropdowns are no longer covered by the bar

## [0.1.62] - 2026-09-17

### Fixed

- Reading progress: light mode no longer looks instantly full (track uses a neutral tint instead of brand@18%; scroll math no longer jumps to 100% on tiny ranges; remeasures after theme toggle)

## [0.1.61] - 2026-09-17

### Fixed

- Favicon light/dark: emit unscoped fallback first, then `prefers-color-scheme` links last so Chromium picks the matching variant (previous order let the fallback always win)

## [0.1.60] - 2026-09-17

### Fixed

- Layout editor: desktop nav / menu link groups stay selectable after reload (layers filter no longer flattens them; menu slots are style targets)
- Nav/footer remount keeps author classes on menu slots (`uppercase`, `hover:*`, …) and promotes styles authored on the slot itself

## [0.1.59] - 2026-09-17

### Fixed

- Save no longer looks frozen after add/remove: UI paints “Compiling styles…” / “Saving…” before heavy `buildPayload`, and waits for pending page CSS JIT to finish (with feedback) instead of blocking the main thread silently

## [0.1.58] - 2026-09-17

### Fixed

- Layout editor: Progress + Page content drop zones visible again (canvas rules now match `data-voodbuilder-editor-scope=layout` in the iframe, not only the outer Filament body class)
- Footer brand/columns use `lg` breakpoint (and tablet device uses mobile logos) so intermediate widths no longer squeeze dual logos / cramped columns

## [0.1.57] - 2026-09-17

### Fixed

- Layout editor mobile + dark: only one brand logo (light/dark variants no longer both visible — theme class is on `html`, not `body`)

## [0.1.56] - 2026-09-17

### Fixed

- Footer tagline / copyright from Brand settings now apply on the published site (slot hydrate reads `data-voodbuilder-config`; editor also syncs GrapesJS component text so save keeps custom copy)

## [0.1.55] - 2026-09-17

### Fixed

- Theme Studio: six palette color tiles (including Footer background) on a single row

## [0.1.54] - 2026-09-17

### Added

- Footer / nav Brand: per-breakpoint visibility for logo and site name (desktop vs mobile)
- Logo shape control: **Natural** (default, wide logos OK) or **Circle** (avatar crop)

### Fixed

- Footer Brand: Tagline and Copyright text fields visible in settings (ensure assets rebuilt after update)
- Footer / nav logos no longer forced into a circle unless Circle shape is selected
- Layout editor: typography on nav/footer menu links (e.g. uppercase) now persists after save/reload and on the published site (styles promoted to durable CSS selectors that survive dynamic menu re-render)
- Footer column headings refresh from the assigned menu name on hydrate (no more stuck “Footer column N”)

### Added

- Theme Studio: **Footer background** color for light/dark palettes; site footer chrome uses `--vx-footer-bg`
- Settings: light/dark favicon uploads (`prefers-color-scheme`); SEO TagManager singleton so page title survives layout `{!! seo() !!}`

## [0.1.53] - 2026-09-17

### Added

- Footer settings (Brand): editable **Tagline** and **Copyright** fields with `{current_year}` / `{brand_name}` text tags (resolved in canvas preview and on publish)

## [0.1.52] - 2026-09-17

### Fixed

- Layout editor logo Choose opens the vmedia browser (wire `mediaLibraryUrl` / `mediaGalleriesUrl` like the page editor)

## [0.1.51] - 2026-09-17

### Fixed

- Layout editor Elements tab: Community Site nav/footer tiles show again (BlockManager `voodbuilder-site_*` IDs matched the foundation allowlist)

## [0.1.50] - 2026-09-16


### Changed

- `edition: "developer"` accepted from config and normalized to `professional`
- Agency matrix adds `dynamic-api.sources` / `dynamic-api.admin`
- API Data Sources moved to companion `voodflow/voodbuilder-dynamic-api` (Core keeps only `.remote.` → `.item.` rewrite inside List repeat)
- Entitlement outages **fail open** on the last snapshot (no Community downgrade after grace); only `active: false` downgrades authoring
- License dashboard payload includes `dynamic_api` product entry
- Remove in-repo work documentation; keep `docs/images/` only

### Fixed

- Licence expiry / entitlement outage can no longer strip paid authoring solely because the remote endpoint is unreachable after the grace window


## [0.1.49] - 2026-09-11

### Fixed

- Series / no-left-sidebar + TOC reading pack uses `data-voodbuilder-reading-column="pack"` so article+aside stay centered (not left-stuck when Tailwind `mx-auto` / max-width utilities are missing from the host Vite CSS)
- Layout chrome editor: Integration tab icon uses Lucide `boxes` (Tabler `components` rendered blank in some builds)
- Layout chrome editor: header is not sticky so Progress + Page content drop zones stay visible under the menu

## [0.1.48] - 2026-09-11

### Added

- **Layout builder → Integration tab** (only when companions register `Voodbuilder::readingPreview()`): typography controls in the inspector; **live preview in Page content**; Primary / Secondary fonts; body size + primary/secondary type scales via Tailwind size tokens (`xs`…`9xl`); **Reset** restores defaults
- Integration CSS vars on `<html>`: `--vp-font-family-doc`, `--vp-font-family-sidebar`, `--vp-font-size-doc`, `--vp-font-size-sidebar`, `--vp-doc-*`, `--vp-sidebar-{h1–h4,p}-{size,weight,leading}`
- `Voodbuilder::readingPreview($channelId, …)` / `ReadingPreviewRegistry` — third-party plugins register sample HTML for the Layout builder Integration tab
- Chrome layout visual editor canvas receives Integration typography CSS vars from the layout record
- Layout chrome editor block sidebar includes **FOUNDATION** blocks (Layout / Basic / Media / Utilities / Site), not only Site nav/footer
- **Chrome layout Progress zone** (optional strip between Header and Page content): drop Reading progress here so it is not nested in the nav; removing it no longer deletes the header. Published bar stays `position:fixed` and pins under sticky/fixed nav (or flush to the top when the menu scrolls away)
- Academy / developer docs: Chrome layout & Integration preview (companion checklist for channels, reading layouts, Integration samples, dedicated sub-themes)
- `Voodbuilder::reservePathPrefix()` / `ReservedPathRegistry` — companions (and third parties) declare public URL prefixes so site-page catch-alls never claim them
- Site-page catch-alls register after boot so reserved prefixes from all packages are known
- Content channel **Search** (`search`) for Theme Map / Layouts; inherits Documentation chrome by default; excluded from search filter pills
- Search results page uses the **page** chrome (narrow reading measure via content width)
- `SearchExcerpt` helper — plain-text snippets without raw Markdown (`##`, `**`, links, …)
- Settings → **Search**: results per page, per channel, snippet length (with config fallbacks)
- Search ranking (title / meta / body), match-centered snippets, and `<mark>` highlights
- Search pagination with deep-linkable `?page=` (keeps `q` / `type`)
- Header search palette: live top matches (Filament-style), keyboard nav, recent searches, link to full results
- Developer docs: PHP SDK [Site search](docs/manual/developer/php-sdk/site-search.md) — how companions make content searchable
- Doc reading typography closer to VitePress (`.vp-doc` sizes/spacing, content width tokens, sidebar weights)
- Site Page admin **SEO** section (meta title, description, robots, canonical) on the morph `seo` relation
- Site Page JSON-LD breadcrumbs (and Article schema for section articles)
- Dynamic templates prefer bound entity SEO (event, exhibitor, …) over the template page title
- `data-voodbuilder-reading-column="content"` for no-rail reading pages (paywalls / simple articles)

### Changed

- Integration typography is edited in the **layout visual builder** Integration tab (formerly Reading), not in Filament Layouts admin; settings apply to all companions on that chrome layout
- Reading layouts are owned by companions (`vdocs::layouts.voodbuilder`, `vtuts::layouts.voodbuilder`); `voodbuilder::layouts.doc` remains a deprecated shim
- Site search uses `PluginLayout::resolve('page')` instead of the doc reading layout
- Documentation search hits show **Topic › Section** as meta, plus a short content excerpt
- Search results UI: card list, softer filters, cleaner hierarchy
- Search filter pills show result counts; empty channels are omitted
- “All” results are a single ranked list with channel headers when the channel changes
- Doc layout: content+TOC as `w-fit` pack beside the sidebar; article uses `--width-vp-content` without filling leftover viewport
- Locked / default dark theme syncs `--default-theme-mode` and Filament Alpine theme store so public pages with comment scripts do not flash light

### Fixed

- With empty page `route_prefix` + `menu_paths`, companions reserve their prefixes so site pages no longer shadow public routes (`/tutorials/…` → 404)
- Search `<mark>` contrast: readable ink on brand tint in light and dark (no inherited light-on-yellow)
- Search pagination: out-of-range `?page=` is clamped so a new query never shows an empty list while the count is > 0
- Public layouts emit a single `<title>` via `{!! seo() !!}` (removed duplicate Blade title)
- Site Page meta description no longer falls back to stripped canvas HTML; uses excerpt → SEO override → site default
- Gated / password-protected pages default to `noindex, nofollow` unless SEO robots is set
- No-rail reading columns stay on `--width-vp-content` even when Tailwind arbitrary `max-w-[var(…)]` utilities are missing from the Vite CSS

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

- Admin license dashboard API: `GET|POST /voodbuilder/admin/license/{status,refresh}` (`schema_version: 1`)
- `CatalogCredentialResolver` — prefers entitlement snapshot credentials, falls back to env CDN token

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
