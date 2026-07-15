# Voodbuilder JS editor — modular layout

This document describes the modularization strategy for the GrapesJS integration layer.
All paths are relative to `resources/js/grapesjs/`.

**Master plan:** see [MODULAR_REFACTOR_PLAN.md](./MODULAR_REFACTOR_PLAN.md) (branch `modular`).

## Principles

1. **Never patch GrapesJS core** — extend via plugins, events, and CSS variables.
2. **Stay inside the package** — no edits to host app or other plugins.
3. **Atomic modules** — small files with a single responsibility.
4. **English documentation** in code comments and README files.

## Layer map (post-refactor)

| Layer | Path | Responsibility |
|-------|------|----------------|
| `core/` | `attrs.js`, `block-tree.js` | Pure helpers, no GrapesJS |
| `chrome/` | `ids`, `slots`, `zones`, `layout/*`, `page/*`, `blocks/*` | Site chrome domain |
| `blocks/` | `settings/`, `dynamic/` | Inspector settings + dynamic type |
| `editor/` | `init.js`, `payload.js`, `inspector.js` | Bootstrap + wiring |
| `plugins/` | `voodbuilder.js` | GrapesJS plugin entry |

## Legacy shims (temporary)

- `editor.js` → re-exports `editor/init.js`
- `block-settings/` → re-exports `blocks/settings/`
- `editor-chrome-layout.js` / `editor-chrome-shell.js` → being replaced by `chrome/layout/*`, `chrome/page/*`
- `_legacy/` — deprecated paths for one release cycle

## Block settings

See `blocks/settings/README.md` (copied from `block-settings/README.md`).

Selection uses `findInspectableRoot()` which:

- Climbs to `[data-voodbuilder-block]` via model tree (no DOM)
- Descends into chrome drop zones
- Nav/footer descriptors use `layoutOnly: true` (layout editor only)

## PHP contract

`Voodflow\Voodbuilder\Contracts\GrapesJsConfigurableBlock` documents server-side config
normalization. JS `blocks/settings` registry is the runtime inspector source of truth.
