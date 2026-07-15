# Voodbuilder JS editor — modular layout

This document describes the modularization strategy for the GrapesJS integration layer.
All paths are relative to `resources/js/grapesjs/`.

## Principles

1. **Never patch GrapesJS core** — extend via plugins, events, and CSS variables.
2. **Stay inside the package** — no edits to host app or other plugins.
3. **Atomic modules** — small files with a single responsibility.
4. **English documentation** in code comments and README files.

## Module map

| Module | Responsibility |
|--------|----------------|
| `block-settings/` | Generic inspector settings for blocks with `data-voodbuilder-block` |
| `editor.js` | Bootstrap only — wires modules, no business logic |
| `editor-layout.js` | Shell DOM + GrapesJS `appendTo` mounts |
| `editor-chrome-layout.js` | Chrome **layout** editor behaviour |
| `editor-chrome-shell.js` | Chrome **page** editor shell (read-only header/footer) |
| `plugins/voodbuilder-grapesjs.js` | DomComponent types + block registration (being slimmed) |

## Block settings (implemented)

See `block-settings/README.md`. Any block can register settings with:

```js
registerBlockSettings({
  id: 'my_block',
  blockIds: ['my_block_id'],
  render: ({ mount, root, editor }) => { /* ... */ },
});
```

Selection uses `resolveInspectableBlockRoot()` which:

- Climbs to `[data-voodbuilder-block]`
- Descends into containers (drop zones, locked shell wrappers)
- Works in page + layout editors

## Next extraction targets

1. `site-chrome/nav-block.js` — nav preview + traits (from `voodbuilder-grapesjs.js`)
2. `site-chrome/footer-block.js` — footer preview + traits
3. `editor/init-dynamic-blocks.js` — dynamic block refresh pipeline
4. `editor/init-inspector.js` — inspector extension registration

## PHP contract

`Voodflow\Voodbuilder\Contracts\GrapesJsConfigurableBlock` documents server-side config
normalization. JS `block-settings` registry is the runtime inspector source of truth.
