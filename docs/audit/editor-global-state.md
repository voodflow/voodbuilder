# Editor global / instance state

## Preferred pattern (current)

Most mutable flags hang on the **editor instance** as `__voodbuilder*` properties (not `window` singletons). Examples:

| Property | Meaning |
|---|---|
| `__voodbuilderPopupMode` | Popup editor mode |
| `__voodbuilderChromeLayoutMode` | Chrome layout editor |
| `__voodbuilderChromeShellMode` | Page chrome shell |
| `__voodbuilderActiveLibrary` | `blocks` vs `components` |
| `__voodbuilderComponentSelectionMode` | Component pick mode |
| `__voodbuilderBlockPins` / `__voodbuilderToggleBlockPin` | Block pin state |
| `__voodbuilderComponentLibraryActions` | Component library menu API |
| `__voodbuilderLayerTreeSorting` (+ `Until`) | Layers drag grace |
| `__voodbuilderLayersSelectionPin` (+ `Until`) | Selection pin |
| `__voodbuilderLayersDragRegistered` | Idempotent layers drag init |
| `__voodbuilderSectionNestingGuardBound` | Idempotent nesting guard |
| `__voodbuilderSettingsChange` / `Depth` | Settings mutation guard |
| `__voodbuilderSchedulePageCssRebuild` | CSS rebuild scheduler hook |
| `__voodbuilderLastDragPoint*` | Drag geometry |
| `__voodbuilderPointerOverTopSpacer` | Top spacer hit testing |

## Window / document globals

| Symbol | File | Role |
|---|---|---|
| `window.__vbSocialShareCopyInit` | `vb-runtime.js` | Idempotent social copy init |
| DOM meta `csrf-token` | `editor-api.js` | API auth |

Avoid introducing new `window.VoodBuilder*` singletons (master plan §13.7). Prefer `editor` instance context or explicit module context object.

## Shared registries (module-level JS)

- Block settings registry (`blocks/settings/registry.js`)
- Layout chrome block registry
- Block pins sets
- Various `Map`/`WeakMap` timers (nav refresh, feedback)

These are file-scoped singletons: acceptable short-term; document ownership when splitting packages.
