# Editor JS inventory

**Root:** `resources/js/grapesjs/`  
**Entrypoint (editor):** `editor/init.js` (~72KB)  
**Plugin entry:** `plugins/voodbuilder.js` → `plugins/voodbuilder-grapesjs.js` (~51KB)  
**File count:** ~145 `.js` files (~1.8MB total source, excluding generated icons JSON)

## Layer folders (existing)

| Path | Role |
|---|---|
| `core/` | Pure helpers (attrs, sanitize, block-tree, component-model) |
| `chrome/` | Layout chrome domain (ids, slots, zones, blocks) |
| `blocks/settings/` | Inspector settings registry |
| `blocks/dynamic/` | Dynamic block types |
| `editor/` | Bootstrap (`init.js`), payload, inspector, modes |
| `plugins/` | GrapesJS plugin registration |
| `_legacy/` | Temporary shims |
| `block-settings/` | Re-export shim → `blocks/settings/` |

## Largest behavioural modules (by size)

| File | Approx. size | Concern |
|---|---:|---|
| `bindings-ui.js` | 105KB | Dynamic data UI |
| `components-ui.js` | 72KB | Component library |
| `editor/init.js` | 72KB | Bootstrap / mode wiring |
| `grapesjs-animated-blocks.js` | 61KB | Animated blocks |
| `tailwind-visual-style.js` | 60KB | Style inspector / export bake |
| `plugins/voodbuilder-grapesjs.js` | 51KB | Core plugin types/blocks |
| `popups-ui.js` | 48KB | Popup admin UI in editor |
| `basic-elements-settings.js` | 47KB | Basic element settings |
| `page-templates-sidebar.js` | 34KB | Templates |
| `editor-chrome-shell.js` | 34KB | Page chrome shell |
| `conditions-ui.js` | (smaller) | Conditions |
| `revisions-ui.js` | (smaller) | History |

## Runtime siblings (non-editor or dual-use)

| File | Role |
|---|---|
| `resources/js/popups-runtime.js` | Public popup runtime |
| `resources/js/site-runtime.js` | Public site helpers |
| `resources/js/popup-css-scope.js` | Popup CSS scoping |
| `resources/js/theme-map/` | Theme Studio React map |
| `vb-runtime.js` | Canvas/public micro-interactions |

## Modes shared by one runtime

`editor/init.js` configures:

- **page** (default)
- **chrome layout** (`chromeLayoutMode` / layout editor)
- **popup** (`popupMode`)
- chrome shell overlays for page editors

Shared: Tailwind autobuild, visual style, layers, canvas toolbars, bindings/conditions (gated by options).

## Init-order dependencies (high risk)

1. GrapesJS + base plugins load
2. `voodbuilder` plugin registers types/blocks
3. Chrome shell/layout registrars mutate canvas structure
4. Bindings/conditions/components attach listeners on `load` / `canvas:frame:load`
5. Multiple features re-bind on `canvas:frame:load` (duplicate listener risk)

## Target structure (from master plan §13.5)

Move toward `resources/js/editor/` with registries; keep bridge until behaviour parity proven. Do not rewrite features during first split.
