---
title: JS plugins
description: Mount companion JavaScript into the visual editor.
---

# JS plugins

GrapesJS stays **vanilla**. Companion UI mounts through the Core bridge.

## Preferred: `registerPlugin`

```js
window.VoodbuilderEditor.registerPlugin({
  id: 'acme-editor',
  async mount(editor, context) {
    const { entitlements, urls, labels, csrf, flags } = context;
    // register commands, panels, DomComponents types…
  },
  unmount(editor) {
    // optional cleanup
  },
});
```

Call before / as the editor boots (separate Vite entry or deferred script).

## Path install: `plugin.js`

Ship `resources/js/editor/plugin.js` from your package:

```js
export default {
  id: 'acme-editor',
  mount(editor, context) {
    // …
  },
};
```

Core discovers siblings via `import.meta.glob` in `plugin-bridge.js`.

## Events

Prefer namespaced triggers:

- `voodbuilder:plugins:booted`
- `voodbuilder:chrome-layout-ready`
- `voodbuilder:site-chrome-updated`
- `voodbuilder:inspector-panel:refresh`
- `voodbuilder:page-css-compiled`
- `voodbuilder:theme-changed`
- companion-specific `voodbuilder:<plugin>:…`

## Fonts from JS

`registerFonts` / `registerFontProvider` are exported through the bridge for catalog extensions.

## Rules

- Never patch `node_modules/grapesjs`  
- Do not hard-code plan names — read `context.entitlements`  
- Keep paid UI out of Core; Core may keep a soft-gate shim only  

::: tip Screenshot needed
Network/Vite graph showing host `init.js` entry + companion plugin chunk.
:::
