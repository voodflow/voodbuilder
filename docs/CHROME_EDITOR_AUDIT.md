# Chrome Editor Audit (July 2026)

Technical review of the VoodBuilder chrome layout / page shell editors after hardening NAV · CONTENT · FOOTER zones.

## Architecture

| Editor | Purpose | Editable zone |
|--------|---------|---------------|
| **Layout editor** (`chromeLayoutMode`) | Defines site shell stored in `voodbuilder_chrome_layouts` | Nav + footer blocks; content slot is a marker only |
| **Page editor** (`chromeShellMode`) | Edits page body inside composed shell preview | Page content slot only; nav/footer read from layout |

All GrapesJS customizations live under `resources/js/grapesjs/` — **never patch `node_modules/grapesjs`**.

## Fixes applied in this cycle

1. **`chrome-editor-guards.js`** — Single source for protected-component checks, context-menu filtering, layer lock icons.
2. **Layout editor** — Fixed NAV / CONTENT / FOOTER drop zones (`data-voodbuilder-chrome-drop-zone`); nav can be re-added after deletion.
3. **Page editor** — Shell parts restored from `chromeShellParts` server payload; bleed stripped from content slot.
4. **Layer panel** — Delete/duplicate hidden for protected zones; drag handle replaced with lock icon.
5. **Block drag** — Drop placeholders visible again inside content/drop zones during drag (previously suppressed in chrome modes).
6. **Save pipeline** — `extractChromeLayoutHtml()` / `extractChromeShellPageHtml()` strip editor-only wrappers before persist.

## Bottlenecks

| Area | Issue | Impact |
|------|-------|--------|
| `editor.js` | ~1.3k lines, monolithic boot | Hard to test; every editor mode loads same bundle (~2.5 MB JS) |
| `buildPayload()` | 15+ sequential export steps | Save latency; any step failure is swallowed with `console.warn` |
| Dynamic block refresh | `refreshDynamicBlocks()` re-fetches all `[data-voodbuilder-block]` on load | Slow chrome shell boot when nav+footer render server-side |
| `ThemePalette::criticalChromeShellCss()` | Large inline CSS per sub-theme | Duplicated across layout preview, page editor, live render |
| Layer tree | Full re-render on many events | Jank on large pages; mitigated with 32 ms debounce but still O(n) tree walks |

## Code quality concerns

1. **Duplicated chrome detection** — `isChromeBleedComponent` exists in both `editor-chrome-shell.js` and `chrome-content-slot-utils.js`. Consolidate in utils only.
2. **Silent refresh loops** — `component:remove` → `scheduleRefresh` → DOM rewrites can fight user actions during bootstrap; `bootstrapping` flag helps but is fragile.
3. **`removable: false` not enforced everywhere** — GrapesJS still allows delete via some commands; guards rely on re-sync. Prefer `editor.on('component:remove:before')` when upgrading GrapesJS.
4. **Layout save without drop zones** — JS export unwraps zones; PHP `ChromeLayoutHtmlSanitizer::unwrapDropZones()` is a safety net. Keep both.
5. **Magic strings** — Attribute names scattered across 10+ files. Consider exporting constants from `chrome-content-slot-utils.js` only (partially done).

## Security

| Risk | Severity | Mitigation |
|------|----------|------------|
| Stored HTML in chrome layouts | Medium | `ChromeLayoutHtmlSanitizer`, `GrapesJsHtmlSanitizer`, binding normalizers on save |
| Page editor saving shell bleed | Medium | `ChromeLayoutManagedContent::stripSiteChromeFromPageHtml()` on save |
| XSS via dynamic bindings | Medium | Existing `GrapesJsBindingStorageNormalizer`; audit binding preview URLs |
| CSRF on save endpoints | Low | Laravel CSRF on all GrapesJS routes |
| **No server-side delete guard** | Low | Protection is client-only; malicious API caller could POST stripped nav — acceptable if layout HTML is admin-only |

## Optimization opportunities

1. **Code-split editor modes** — Dynamic `import('./editor-chrome-layout.js')` only when `chromeLayoutMode`; same for shell.
2. **Cache layout render** — `ChromeLayoutRenderer::render()` output keyed by layout id + updated_at for page editor `chromeShellParts`.
3. **Debounce layer icon patch** — `patchChromeZoneLayerIcons` on `layer:render` only (already partially done).
4. **Reduce `pointer-events: none` scope** — `[data-voodbuilder-chrome-shell-locked]` blocks all interaction; page content slot explicitly re-enabled (see `editor.css`).
5. **PHPUnit for JS contracts** — Add tests for `ChromeLayoutHtmlSanitizer::unwrapDropZones` and `composeForPage` structure (partial coverage exists).

## GrapesJS upgrade checklist

After any `grapesjs` semver bump:

1. Smoke-test layout editor: delete nav → drop new nav → save → reload.
2. Smoke-test page editor: layer context menu on Header/Footer/Content; lock icons; drop block in content.
3. Verify `Layers.setLocked`, `layer:render` event, sorter placeholders (`.gjs-plh`).
4. Confirm `component.components(html)` still replaces children for shell sync.

## Recommended follow-ups

- [ ] Extract `chrome-editor-guards` tests (Vitest or Playwright smoke)
- [ ] Server-side validation: reject page HTML containing `site_nav_*` / `site_footer_*`
- [ ] Unify footer/nav width CSS variables across layout editor, page editor, live (`ThemePalette` audit — in progress)
- [ ] Split `editor.js` into `editor-boot.js` + mode plugins
