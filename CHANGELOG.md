# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.11.87] - 2026-10-27

### Added

- **Fullscreen nav overlay settings + full-bleed bar + explicit Close.** The top bar no longer boxes to `--width-vp-layout` (edge-to-edge with padding). Overlay layout is configurable: `split` (50/50 image + two menu columns) or `aside` (wide image, compact panel), plus an overlay image picker. A floating Close (X + label) sits on the overlay so authors always have an obvious dismiss control.

## [1.11.86] - 2026-10-27

### Changed

- **Fullscreen navbar settings no longer show Menu position (or notifications).** Those controls belong to the classic inline navbar; `site_nav_fullscreen` places links in the open overlay via Admin placements `main` / `header_extra`. Content-width / “riduci” stays hidden on chrome navs by design — the shell is already full-bleed.

## [1.11.85] - 2026-10-27

### Added

- **`site_nav_fullscreen` — Faselunare-style fullscreen two-column overlay nav for Chrome Layouts.** Remote Library HTML headers cannot own the Header zone, keep scripts through save, or bind Admin menus; this server block does. Hamburger opens a full-bleed overlay: left column from placement `main`, right column from `header_extra`, with dashed placeholders when a placement has no items. Registered in the layout Site sidebar next to Navbar; JS boots via `site-chrome-runtime` (with a site-scripts fallback).

## [1.11.84] - 2026-10-27

### Fixed

- **Chrome Layout editor can open the remote Elements catalog (nav blocks included).** The page editor already passed `elementsCatalogs` / `elementsSourceUrl` into the Grapes config; the layout editor gate never did, so Library stayed on local foundation blocks only even when Elements was installed and the remote catalog token was set. `EditorChromeLayoutEditorGate::config()` now wires the same Source API as the page editor, with `includeTemplates: false` so the Templates bundle stays out of chrome shells.

## [1.11.83] - 2026-10-15

### Fixed

- **The second Content edit on a dynamic block is no longer swallowed while the inspector still shows it applied.** The reported gesture was: type `1` → the preview and the canvas agree; type `6` in the same box → the preview still says `1`, the editor reloads showing `1`, and only the next save/reopen makes `6` stick. The write had landed on a component Grapes had already thrown away. A dynamic block re-renders its preview by replacing its children (`component.components(fresh.innerHTML)`), which detaches every model below the block root, while the inspector deliberately keeps its form mounted (`canReuseForm` requires the same root model, and the root is exactly the node that survives). Each Content control was built once with the field component of that first render and wrote into that captured reference: the *first* write found it still attached and reached `writeFieldToDynamicConfig` through `dynamicConfigOwner` → block root, but the refresh that write triggered detached it, so every later keystroke in the same box walked a parent chain that ends below the root, `dynamicConfigOwner` returned `null` and the write fell through to the static-section path — it rewrote the text of an orphan node: no config change, no preview refresh, no dirty mark, no persistence, while the input kept showing the typed value. Controls now resolve the live component on every write instead of holding the render-time one: `resolveDeclarativeFieldComponent` short-circuits when the bound field is still on the canvas (`isComponentAttachedToCanvas`, parent chain reaching the wrapper — the fast path, so nothing changes for a static section) and otherwise asks the control's locator for the field that replaced it. The locator (`createFieldLocator`, `appendDeclarativeItemEditors`) is positional — a dynamic render rewrites values inside the same markup, so the same key at the same index is the same field — and it is keyed so a block the server reshaped falls back to the old bound component instead of writing into a different field. The Content panel feeds those closures from the block root (`liveFields` / `liveItems` recomputed on the live tree), which is the one node the refresh never replaces. `writeFieldText`, `writeStatusValue`, the rich-text and icon hosts all keep their existing semantics for an attached field, so the 1.11.80 stale-response/queue guards, the 1.11.81 `wire:ignore.self` freeze and the 1.11.82 canvas attribute sync are untouched — this only changes *which* component a control writes into, never how the write is applied. Trade-off: resolution is by position and key, so a block whose server render both renames a field and reorders the fields would fall back to the bound component (previous behaviour) rather than guess.

## [1.11.82] - 2026-10-15

### Fixed

- **The canvas element of a dynamic block now carries the config the author just typed, without a re-render.** A declarative Content field wrote the block config with `{ silent: true }` (since 1.11.73) on purpose: a real Grapes `change:attributes` would run the model-side cascade per keystroke — `attrUpdated` re-syncing the block attributes and `changesUp` pushing an undo entry plus an editor `update` — and the view side of that event, `ComponentView.updateAttributes()`, clears and re-stamps *every* attribute of the element, which is where 1.11.81's `wire:ignore.self` freeze would be lost on a Livewire mount root. The price was that no view render ran either, so `data-voodbuilder-config` on the canvas element kept the pre-edit snapshot (e.g. `per_page: 5`) while the model, the rendered preview and every later Save already carried the new one (`3`); the canvas only caught up on a later, unrelated Grapes resync — a visibly contradictory value in the editor with no persistence consequence, because the payload is serialised from the model. `writeFieldToDynamicConfig` now mirrors the freshly written config onto that single element attribute from the model (`syncBlockConfigAttrToView`, through `component.getView().el`), so the canvas and the model agree the moment the field changes. The sync emits no Grapes event and schedules no refresh, so the 1.11.80 debounce/queue is untouched and the preview request count stays one per edit, never one per keystroke. The write is deliberately the narrow half of a change — the one attribute that was written — not a full `updateAttributes()`, so the element is not cleared and re-stamped and any other attribute still relies on Grapes' own resync exactly as before.

## [1.11.81] - 2026-10-15

### Fixed

- **A dynamic (Livewire) block keeps the author's root styling and its content-width choice through every component interaction.** Narrowing `demo_pages_explorer` with the width button held until the block was used: the first search keystroke made the block go full-width again. The chrome this renderer stamps on a dynamic block root — the measure utilities (`w-full mx-auto max-w-[80rem]`), the author's own `lg:my-20`, `id`, `data-vb-*` paint, `data-voodbuilder-content-width` and `data-voodbuilder-block` — all land on the node Livewire owns (the mount root, the one carrying `wire:id` / `wire:snapshot`). A Livewire request re-renders the component from its own Blade markup, which knows nothing about any of that, and morphs the response onto that element: morph's `patchAttributes()` walks the *incoming* attribute list and removes every attribute it does not carry, so the width, the margin and the block identity were gone at the first keystroke. The lesson is that a long-lived component's own attributes are not the renderer's to keep, so the publish path now freezes them with Livewire's own mechanism for this: `applyLivewireRootSelfProtectionToHtml()` writes `wire:ignore.self` on the mount root, which makes morph answer `childrenOnly()` — the element's own attributes are skipped while its children keep updating, so the search input and the result list still work. The attribute is written on the element that already exists and only on the mount root: no wrapper is added, no DOM level changes, no utility list moves (the 1.11.76 restructure cannot come back), and `script` / `style` / `link` siblings are never touched. It is applied after the author chrome merge and before the content-width wrapper, so a block with a genuine measure shell keeps the measure on the shell and the freeze on the mount inside it. Canvas preview is deliberately excluded. Note the trade-off: the root's own attributes are now static for the component's live session, which is correct for template-static roots (the normal case) but means a component whose root *classes* are computed server-side per render would keep its first-render classes until the page is reloaded.

## [1.11.80] - 2026-10-15

### Fixed

- **The value an author types into a dynamic block's Content field is no longer reverted by the re-render that very keystroke triggers.** Setting "Results per page" on the `demo_pages_explorer` Livewire block to `3` reverted to `5` on reload, in the canvas, in `builder_payload['html']` and in the published Livewire snapshot. Three parts of one round trip were at fault. (1) The preview refresh is a network call: the config it renders is captured when the request starts and merged back into the component when the response lands (`refreshDynamicBlockComponent`), so an author who kept editing while that call was in flight had a **pre-edit snapshot applied over the newer value** — the render that had been asked for *before* the change silently overwrote the change, with no error anywhere. Responses are now dropped when the component no longer carries the config they were rendered for (`isStaleDynamicRender`), and the refresh queued by the edit is the one that renders the newest value. (2) A refresh requested while any render was running was **dropped** by `scheduleDynamicBlockRefresh` / the in-flight guard, so the newest config never even reached the server and the reverted value stuck: those requests are queued per component and drained as soon as the last running render finishes (`dynamicBlockRefreshQueue` / `flushQueuedDynamicBlockRefreshes`). (3) `writeFieldToDynamicConfig` writes the config with `{ silent: true }`, which raises no Grapes change event, so nothing told the save gate the page had changed; the write now calls `__voodbuilderMarkPageUnsaved()` when it really changes a value, and a save of the author's edit can no longer take the read-only path.
- **An emptied number field is a moment of typing, not a value.** `coerceFieldValue()` mapped an empty box to `0`, so backspacing "5" before typing "3" wrote `per_page: 0` — a render with a count nobody chose, and a `0` persisted in the payload when the author saved right after clearing. An empty number box now writes nothing (and is still reported as handled, so the static-section fallback never paints the emptied marker text into the preview DOM). Reporters: "imposto 3, salvo e ricarico: il campo torna a 5", plus the same loss on the `heading` text field.

## [1.11.79] - 2026-10-15

### Fixed

- **The content-width control is back on dynamic (Livewire) blocks.** 1.11.76 added a guard to `shouldShowContentWidthToolbar()` that hid the toolbar button whenever a dynamic block had no explicit content shell — which is the *normal* case, because a Livewire mount ships its own markup and no shell, so `findSectionContentWrapper()` returns `null` and the condition was always true on the demo block. The guard is gone: a dynamic block always owns a measure. `resolveContentWidthTarget()` now resolves to a genuine author content shell when one exists, otherwise to the **block root itself** — never to an inner layout node. Because the root only receives measure utilities / `data-voodbuilder-content-width` / inline measure, the block is never restructured: no wrapper is synthesized inside the block and no child is absorbed (`ensureSectionContentWrapper()` still refuses to create a shell for a dynamic host), so the shrink-and-relayout regression 1.11.76/1.11.77 fixed cannot come back. The button stays in the same canvas toolbar slot as 1.11.72 (`arrow-autofit-width`, next to the class / dynamic tools), where authors already look for it.
- **The author's width choice now survives Save on a dynamic block root.** The save normalizer (`EditorDynamicBlockAttributeNormalizer::pruneForeignAttributes()`) kept only `data-voodbuilder-block`, `data-voodbuilder-config`, `class` and `id`, so `data-voodbuilder-content-width` was silently dropped on every save: the block looked right in the canvas and reverted to its unconstrained width on the published page, and the editor could no longer restore the mode. The attribute is now preserved (corrupted config leaks such as `limit=""` are still pruned).
- **A custom width reaches the published page even when the inline measure is not the source of truth.** The custom cycle step wrote the max width only as inline `style`; a dynamic block root persists its measure through Tailwind classes (and 1.11.79 prunes the rest), so `custom` published as full-bleed. The cycle now also emits a durable `max-w-[<custom>]` utility (mirroring the `max-w-[80rem]` of the normal step), so Normal, Full and Custom all compile into the page CSS and render as chosen.

## [1.11.78] - 2026-10-15

### Fixed

- **Spacing — and every other neutral Style field — now shows the value the legacy `dark:` class on the element actually carries.** 1.11.76 made neutral groups theme-free, so the panel began reading them only at the plain breakpoint prefix, while a page authored before that still carries `dark:lg:my-20`. The canvas forces `.dark` from the editor theme, so the margin was visible there, but `resolveSpacingState()` / `syncSpacingBox()` found no class at `lg:` and rendered the Margin and Padding boxes empty: the author could neither see the state nor rewrite it cleanly, and the writer could not drop a `dark:` twin it did not know existed. Reads for theme-neutral groups now fall back to the legacy `dark:` twin (`resolveGroupValueAtBreakpointWithLegacyDark()` / `resolveGroupValueExactWithLegacyDark()`), guarded by `acceptsLegacyDarkFallback()` so a theme-scoped background paint — or a stop name shared with text gradients (`from-*`, `to-*`, `via-*`) — is never misread as a neutral value.
- **A legacy page heals as the author works, and a plain re-save really persists the fix.** Two gaps kept `dark:lg:my-20` alive. First, the heal only ran at Save, so the canvas and the panel disagreed until then; `healLegacyNeutralThemeClassesForComponent()` now runs on element selection and on the panel resync, guarded by `__voodbuilderTwStyleApplying` so it never fights an in-flight edit. Second, and worse: a Save with no other edit is skipped outright when the payload hash matches the seeded baseline, so on a page whose only problem was the legacy class the request was never sent and the broken page stayed broken in the database forever. The tree is now healed immediately *after* the baseline is seeded and the page is marked unsaved when anything was healed, so the first Save takes the mutating path and posts. Reporters: "in editor dark vedo il padding ma in colonna editor sono vuote le caselle" and "in pagina, il dark ha il margine e il light no".
- **A neutral `dark:` can no longer survive a Save on *any* path.** The tree heal is skipped by a read-only Save (`buildPayload(editor, { mutate: false })`) and can miss a component whose classes only live in a `class` attribute (a dynamic block re-stamped from the server preview on refresh). `healNonThemeScopedThemeClassesInHtml()` is now the last step before the payload HTML is stored, so the invariant holds unconditionally: a saved payload never carries a neutral `dark:` utility, and therefore never a class the light page would drop.

### Verified

- The reported "the healed class is in the HTML but not in the CSS" was **not** an ordering bug: `payload.js` heals before `editor.getHtml()`, and `EditorGate::normalizePayload()` heals before `resolvePublishedPageCssForSave()`. The earlier count of `lg:my-20` in the dirty payload was a substring artifact of `dark:lg:my-20`; HTML and compiled CSS were consistent (both dark-only), which is why the light page applied nothing. `EditorPastedComponentNormalizerTest::test_resolve_published_page_css_compiles_the_class_the_legacy_dark_heal_added` pins the real invariant — every class in the saved HTML is defined by the saved CSS — for the page as it was saved before and after the heal.
- Reproduced on the demo page (`livewire-demo`, block `demo_pages_explorer`): before, the Margin box was empty and the payload carried `dark:lg:my-20` with `.dark\:lg\:my-20` in the CSS; after a Save it carries `lg:my-20` and `.lg\:my-20`, and the published page renders the same margin with `<html class="">` (light).

## [1.11.77] - 2026-10-15

### Fixed

- **A dynamic (Livewire) block no longer publishes wrapped in its own preview layout row.** 1.11.76 stopped the editor from *creating* the poison, but a page already saved with it still published shrunken. The width toolbar had stamped the shell markers (`voodbuilder-editor-container`, `mx-auto`, `w-full`, `max-w-[80rem]`, `data-voodbuilder-role="content"`) onto the block's inner preview row — a `flex … sm:justify-between` node — and `captureAuthorContentWidthShells()` picked the *first* shell it found, so `wrapRenderedInAuthorContentWidthShell()` copied that flex row's classes as the author measure and wrapped the whole mount in it. Shells are now validated as a real content measure: `contentWidthShellElements()` skips nodes carrying Tailwind layout utilities (`flex`, `grid`, `justify-*`, `items-*`, `gap-*`, `order-*`, `col-*`, `row-*`, …) and `wrapRenderedInAuthorContentWidthShell()` takes the first *genuine* shell, or none — a block the author never constrained stays full-bleed, exactly as the canvas renders it. The author's real width choice (a shell with no layout of its own) still applies.
- **Authored root classes and attributes on a dynamic block now reach the published element.** `replaceNodeWithRenderedHtml()` swapped the author node for the component markup and dropped its root `class` / `id` / `data-vb-*`, so a Style Manager edit on the block root (margin, padding, radius, background, borders) applied in the canvas and never on the page — the "it shows in the editor and does nothing on the site" report, independent of the theme. The renderer now merges the author's root utilities onto the rendered root via `EditorBlockClasses::mergeAuthorRootClasses()`: the component's own Blade classes stay first and are never dropped (so a template change is never pinned by stale saved classes), author tokens are appended, and editor plumbing (`voodbuilder-editor-*`, `container`) is skipped because it would make the export healer treat the mount as a section needing a wrapper. `id`, `data-vb-*` chrome and `data-voodbuilder-content-width` ride along, and `data-voodbuilder-block` is re-stamped so the published node still says which block it is.
- **Existing poisoned payloads self-heal on Save.** `EditorImportedTailwindSupport::healDynamicBlockContentShells()` runs in `EditorGate::normalizePayload()` *before* the section healer, unstamping the measure classes / attributes (and the matching `max-width` / `margin-inline` / `width` inline declarations) from any layout node inside a `data-voodbuilder-block` subtree while keeping its layout classes and children. `healExistingEditorSections()` no longer synthesizes a content wrapper inside a dynamic block at all — the server render is the canonical markup there — so it cannot restamp the node it just cleaned. The DOM is the source of truth on the render path too, so a payload that is never re-saved still publishes correctly.
- **The editor's dynamic refresh no longer re-stamps a layout node.** `captureContainerAuthorClasses()` / `restoreContainerAuthorClasses()` in `voodbuilder-editor.js` shared one selector with the shell healer and blindly copied the captured class list back onto the first matching node after `component.components(fresh.innerHTML)`, which is how the poison survived every refresh. They now share `resources/js/editor/core/content-shell-classes.js` with the canvas toolbar: a node carrying layout utilities is never captured as a container, is unstamped instead, and never receives a captured measure class list. `findSectionContentWrapper()` also skips layout children, so `ensureSectionContentWrapper()` can no longer absorb the rest of a block into its own flex row.

## [1.11.76] - 2026-10-15

### Fixed

- **Dynamic (Livewire) blocks are no longer restructured in the editor.** The content-width toolbar treated a dynamic block as a section that still needed a content shell: `findSectionContentWrapper()` falls back to "any non-decorative `div` / `article` / `main` child", which for a dynamic block is its own inner preview layout (e.g. the `flex … sm:justify-between` header row). `ensureSectionContentWrapper()` then stamped that row as the section content wrapper (`voodbuilder-editor-container`, `mx-auto`, `max-w-[80rem]`, `data-voodbuilder-role="content"`) and `absorbSectionContentIntoWrapper()` moved the rest of the block — its `<ul>` list — inside it. That is why the block shifted and shrank in the canvas and looked shrunken on the published page: the broken markup was what got saved. Dynamic blocks own their DOM, so they are now excluded from the wrapper fallback, from shell synthesis, and from offering the width cycle when the block has no explicit shell. Livewire content width at publish time still belongs to `EditorDynamicBlockRenderer`.

### Changed

- **Style panel: only background paints are theme-scoped.** The Dark toggle used to prefix *every* group with `dark:`, which meant an author editing spacing in dark mode produced `dark:lg:my-20`. The canvas forces `.dark` from the editor theme so the margin showed there, but a published page starts light and never applied it — the author had to repeat the same edit twice. Background color (and its opacity), background image layout and gradients are the only values that legitimately differ per theme; they keep `dark:` exactly as before. Typography, spacing, dimensions, borders, radius, shadows and text color are now authored theme-neutral at the plain breakpoint prefix, and rewriting one of those groups also drops the stale `dark:` siblings an earlier policy left behind. A save-time pass (`healNonThemeScopedThemeClassesForExport`) strips `dark:` from non-background utilities still sitting in existing pages, so a page carrying `dark:lg:my-20` self-corrects on the first Save; shared names that belong to both background and text gradients (`from-*`, `to-*`, `via-*`, `bg-gradient-*`, `bg-none`) are left alone because their paint cannot be inferred.
- **Dark-scope warning scoped to what it can affect.** With theme variants now limited to backgrounds, the generic "dark: variants will not apply" copy was too broad for a warning that only ever matters for paints. The `never` / `only-dark` messages and the viewport cascade hint now say explicitly that the theme applies to background paints, in both `resources/lang/en/pro.php` and `resources/lang/it/pro.php` (the Italian file was also missing the two warning keys).

## [1.11.75] - 2026-10-15

### Fixed

- **Content inspector empty on chrome-shell pages.** The chrome-shell editor branch skipped the initial dynamic-block refresh entirely, so page-content blocks never received the rendered editor preview. Declarative `data-vb-field` markers exist **only** in that preview — never in the saved page HTML — so the Content tab had no fields to build and fell back to "No settings for this element" for every configurable block. Chrome parts (nav/header/footer) stay untouched, as before: re-fetching them only remounts Maps embeds. Page-content blocks now refresh through `selectContentRefreshBlocks()`.
- **Orphan `#id` rules are dropped on Save.** Rules persist by id, so an id that no longer exists could only come from an earlier render: it painted nothing on the published page while still matching the canvas right after Save. `stripOrphanIdRules()` now removes bare `#id` / `html.dark #id` rules whose id is absent from both the saved HTML and the live tree, keeping the page-surface wallpaper id that chrome-shell Save writes without HTML. Pseudo and descendant selectors are never touched.

### Added

- **Dark-scope warning in the Style panel.** The canvas forces `.dark` from the editor theme, so `dark:` variants always look right while authoring even on a page that publishes light — the trap behind "I set a margin, it showed in the editor and did nothing on the site". The viewport strip now names the case: `never` (theme toggle hidden + light/solid default → the variants are dead code) or `only-dark` (the page can be dark but starts light → the values are dark-only overrides). Server flags: `publishedDarkScope` + `publishedDefaultDark`.

## [1.11.74] - 2026-10-15

### Fixed

- Solid **theme** background paints are no longer baked into `#id`. `composeTranslucentBackgroundColor()` used to paint `bg-vp-*` solid at 100% opacity, resolving the token to a hex through `swatchHexForUtility()` — which reads the *editing canvas* theme. Authoring in a light canvas therefore wrote `#id { background-color:#ffffff !important }` (plus the same white dark companion), so a block that relies on `bg-vp-bg*` turned **white right after Save** and only came back dark on reload, once the orphan ids stopped matching. The frozen hex also defeated theme switching, because `bg-vp-*` resolves through `var(--color-vp-*)` per theme and cannot be pre-resolved. The utility now owns the paint at 100%, exactly like non-theme colors; theme.css ships the `.bg-vp-*` utilities, so the canvas needs no fallback. Opacity below 100% still paints rgba.

## [1.11.73] - 2026-10-15

### Added

- Declarative Content-panel fields inside **dynamic** blocks. A `data-vb-field` marker that sits inside a block rendering from `data-voodbuilder-config` now writes into the block config and re-renders the preview (debounced), instead of patching the canvas DOM: the old behaviour was discarded on the next refresh and never reached `toHtml()` on the published page. Static sections keep writing into the HTML exactly as before. The marker's `data-vb-field-type` drives the config type — `number` → number, `status` → boolean, everything else → string. Server blocks and the Livewire tier can therefore expose editor settings with markup only, no plugin JS.

## [1.11.72] - 2026-10-15

### Fixed

- Livewire blocks keep the author's content-width shell on the published page. The mount carries no shell of its own, so the shell is rebuilt around it (`EditorDynamicBlockRenderer::wrapRenderedInAuthorContentWidthShell()`) instead of patched onto regenerated markup — the patch helper only finds shells the Blade output already contains. Previously the width toolbar's choice was dropped on publish while the canvas kept showing it; a block the author never constrained still stays full-bleed, matching the canvas. `captureAuthorContentWidthShells()` now also captures the shell tag so it can be rebuilt faithfully.

## [1.11.71] - 2026-10-15

### Added

- `EditorLivewireBlock` contract, registered with `Voodbuilder::editorServerBlock()`: a server block whose published output is a mounted Livewire component. The canvas keeps rendering the static `toPreviewHtml()` — the editor never mounts Livewire, so save/load, anchoring and Tailwind compilation are unchanged — while the published page mounts through `Livewire::mount()`, keyed by the author node id so the same block twice on a page yields two components. Props reach the component only through `propsFromConfig()`, which keeps the config→component mapping an allowlist.
- Livewire block styling: utilities that appear only inside the component view are compiled into the published stylesheet from the block preview markup (`EditorLivewireBlockCss`), cached by markup. Share utilities between the component and preview views — ideally the same partial — or the mount renders unstyled.
- A missing or unregistered component degrades to the static preview instead of breaking the page. `livewire/livewire` is listed under `suggest` (already installed transitively by Filament v5).
- Livewire blocks deliberately bypass `EditorRichContentBlockAdapter::prepareBlockHtml()`, whose class / placeholder / button normalizers rewrite regenerated markup (interactive `<button>` controls morphed into anchors, migrated class names) and break hydration; author root classes are likewise not re-applied over the mounted component root.

## [1.11.70] - 2026-10-14

### Changed

- README Developer/Agency purchase links include AnyStack affiliate `?via=arf178`

## [1.11.69] - 2026-10-12

### Fixed

- Full-page heal uses `healExistingEditorSections()` (and multi-section `ensureEditorLayoutShell` short-circuit) so saved pages are never re-wrapped into a single paste shell

## [1.11.68] - 2026-10-12

### Fixed

- Hero plasma stage is decorative: content nested inside `vb-hero-plasma__stage` is unwrapped on save/render so CTAs stay clickable (`pointer-events` / `aria-hidden`)
- Content-width shells no longer persist measure via `style="width/max-width/margin…"` — Tailwind utilities + `data-voodbuilder-content-width` only
- Footer chrome `.text-center` no longer recenters `[data-voodbuilder-footer-menu-cols]` (proper `:not` selector, not a landing.css override)

## [1.11.67] - 2026-10-12

### Fixed

- Site footer menu columns stay left-aligned (chrome `text-center` utility no longer recenters link lists)

## [1.11.66] - 2026-10-12

### Fixed

- Image credit links forced to white; strip readability aligned with Vmedia dark+brand-tint credits chrome

## [1.11.65] - 2026-10-12

### Added

- Image settings: visible lightbox switch (full-size modal on/off) with short hint

### Changed

- Standalone image credit links use theme brand colors (aligned with Vmedia gallery credits)

## [1.11.64] - 2026-10-12

### Added

- Standalone Image settings: optional lightbox checkbox (`data-vb-lightbox`) to open the image full-size on the published page

### Fixed

- Image Credits field is a multi-line textarea (no single-line truncation); Unsplash-style HTML credits keep safe `https://` links as clickable anchors in the “i” panel
- Theme Studio and Voodbuilder Settings now honour Filament Shield (`View:ThemeStudioPage` / `View:VoodbuilderSettingsPage`) so roles without those abilities no longer see the Voodbuilder admin nav
- Model Integration policy uses `AdminAuthorization` instead of always-allow
- Subscriber page access checks explicit role/permission assignment (avoids Gate `before` wildcards like `super_admin`)

## [1.11.63] - 2026-10-12

### Added

- Standalone Image settings: Credits field (same as gallery) with top-right “i” badge and click-to-reveal panel on canvas and published pages

## [1.11.62] - 2026-10-12

### Fixed

- Editor no longer requests `/blocks/render` for static HTML blocks (`vb-bg-image`, catalog Elements). Those returned 404 and logged `could not refresh dynamic block` when changing styles (e.g. link color) on a page that also had a media hero. Gate now exposes `serverRenderableBlockIds`; 404 remains a silent no-op.

## [1.11.61] - 2026-10-08

### Changed

- Community package license metadata aligned with the public EULA: `composer.json` and `LICENSE` are now **MIT** (matches Packagist / License Agreement for VoodBuilder Community).

## [1.11.60] - 2026-10-08

### Fixed

- Front still showed a prior Color `#id` rgba (e.g. red) after the Style class changed to another utility (`lg:bg-amber-500`): Grapes `removeStyle` did not drop the property from serialized CSS. Clear via `setIdRule` rewrite, and on Save rewrite/strip `#id` background-color from the author CSS string so Tailwind owns opaque colors.

## [1.11.59] - 2026-10-08

### Fixed

- Save after Style Color changes: `reconcileBgColorOpacityForExport` no longer `clearStyleProperty`s every node in the tree (that dominated Save). It scopes to the content slot, skips unrelated components, uses a light clear, and rebuilds the canvas opacity `<style>` once per Save.

## [1.11.58] - 2026-10-08

### Fixed

- Vitest: hydrate no longer expects background-color on Grapes inline.

## [1.11.57] - 2026-10-08

### Changed

- Author styling rule: do not hydrate CssComposer background paints into Grapes inline — Color/photo/gradient stay on Tailwind utilities + `#id` CSS only (no `style="background-*"` as source of truth).

## [1.11.56] - 2026-10-08

### Fixed

- Style Color recolor after hydrate: Grapes inline still held the previous `#id` rgba (e.g. gray) and `collectAuthorIdCssFromComponents` / bake preferred that inline over CssComposer, so Save kept shipping the old hue while the canvas showed the new `vp-*` class. Prefer `#id` background-color on export, clear hydrated inline on Color paint/reconcile.

## [1.11.55] - 2026-10-07

### Fixed

- Style Color Clear / recolor left a stale `#id { background-color: rgba(…) }` on Save (public page kept previous red while the editor showed transparent or `vp-bg-alt`). Reconcile paints from utilities before export, clear both author node and visual-target `#id` rules on change, and stop re-flushing a stale canvas opacity map during bake.

## [1.11.54] - 2026-10-07

### Fixed

- Style Color opacity on dynamic blocks: `data-vb-bg-color-opacity` survived Save/`#id` rgba paint but was dropped on Grapes parse (`voodbuilder-dynamic` isComponent attribute whitelist) and remount `setAttributes`, so refresh painted solid theme red and the first Save could keep a stale black `#id`. Preserve `data-vb-*` + id on parse, merge remount attrs, flush canvas opacity paints into CssComposer before export, and recover opacity from saved rgba on load.

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
