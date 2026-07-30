# Editor JS plugins (companion packages)

For release packaging: **GrapesJS core stays vanilla**. VoodBuilder owns the editor shell (`init.js`). Commercial features live in companion packages and talk to Core through a stable bridge.

## What stays in VoodBuilder core

| Area | Why |
|------|-----|
| Editor shell, canvas, chrome, soft-gates, entitlements UI | Product shell + licensing |
| Soft-gate / upsell when a companion is missing | Core must boot without paid packages |
| Shims like `popups-ui.js` (`import.meta.glob` → companion) | Build succeeds when package absent |
| Shared primitives (dialogs, form UI, block settings registry) | Avoid duplicating chrome |

## What moves to companion packages

| Package | Owns (JS) |
|---------|-----------|
| `voodbuilder-popups` | Popup library UI + public `popups-runtime` |
| `voodbuilder-components` | Components library sidebar, instance type helpers, code import UX |
| `voodbuilder-dynamic-data` | Bindings / Make dynamic / collections Pro UI |
| `voodbuilder-templates` | Template authoring extras beyond core list/marketplace |

**Rule:** if a feature is sold as a plugin, its JS lives in that plugin’s repo — not in `voodbuilder/resources/js/editor/*` (except a thin shim).

Today Popups already follow this pattern. Components / Dynamic Data / Templates UI still live in Core and should be extracted next (same bridge).

## JS communication API

### 1. `registerEditorPlugin` (preferred)

Companion entry (`resources/js/editor/plugin.js`):

```js
export default {
    id: 'voodbuilder-popups',
    mount(editor, context) {
        // context.entitlements, context.urls, context.labels, context.csrf, context.flags
        registerPopupsUi(editor, context);
    },
};
```

Core discovers sibling path installs via `import.meta.glob` in `plugin-bridge.js`, or companions call:

```js
window.VoodbuilderEditor.registerPlugin({ id: '…', mount });
```

before / as the editor boots (separate Vite entry or deferred script).

### 2. PHP SDK + EditorGate payload

PHP registration (`Voodbuilder::editorBlock`, entitlements, URLs) remains the source of truth. The editor boot context mirrors `EditorGate` so JS never hard-codes plan names.

### 3. GrapesJS events

Prefer namespaced triggers:

- `voodbuilder:plugins:booted`
- companion-specific `voodbuilder:<plugin>:…`

Do not patch `node_modules/grapesjs`.

## Vite strategy

| Approach | When |
|----------|------|
| Single editor entry + `import.meta.glob` shims | Default for Filament path/composer installs |
| Companion Vite entry + `window.VoodbuilderEditor.registerPlugin` | Large isolated chunks / public site runtime (e.g. popups-runtime) |

Host `vite.config.js` keeps the Core `init.js` entry. Companions do not need a host entry unless they ship public runtime JS.

## Extraction order (release wave)

1. Popups — done (shim + companion)
2. Components — move `components-ui*`, instance helpers; leave soft-gate stub in Core
3. Dynamic Data — move `bindings-ui` Pro pieces
4. Templates authoring — keep list/apply in Core; authoring extras in companion

## Related

- PHP SDK: [SDK_PLUGIN_API.md](./SDK_PLUGIN_API.md)
- Fonts (Fontsource core + plugin providers): [FONTS.md](./FONTS.md)
- Commercial wave notes: [progress/commercial-plugin-wave.md](./progress/commercial-plugin-wave.md)
- Bridge implementation: `resources/js/editor/plugin-bridge.js`
