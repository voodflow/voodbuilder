# Block settings module

Generic inspector settings for Editor blocks that expose custom configuration UI
beyond default Editor traits.

## Design goals

- **One resolution path** for every editor mode (page, layout, popup).
- **Atomic helpers** instead of per-block selection hacks.
- **Declarative descriptors** — blocks opt in with `blockIds` / `matchBlockId`.
- **No Editor core patches** — integration uses public APIs and DOM mounts only.

## Architecture

```
blocks/settings/
├── select.js      # Resolve `[data-voodbuilder-block]` roots from any selection
├── registry.js    # Descriptor map + normalization
├── ui.js          # Inspector mount + Editor event wiring
├── index.js       # Public API
└── README.md
```

## Registering a block

```js
import { registerBlockSettings } from './blocks/settings/index.js';

registerBlockSettings({
    id: 'my_block',
    blockIds: ['my_block_id'],
    // layoutOnly: true — show custom settings only in chrome layout editor
    // Optional: blockIds: ['a'], matchBlockId: (id) => id.startsWith('prefix_'),
    render: ({ mount, root, editor }) => {
        mount.appendChild(/* custom settings UI */);
    },
});
```

Call registration from `wireInspector()` (or the plugin bootstrap) **before** `registerSettingsUi()`.

Custom `findRoot` / `matchesRoot` remain supported for non-standard roots (e.g. newsletter
forms located by `data-voodbuilder-form` instead of `data-voodbuilder-block`).

## Selection contract

Inspector settings follow a single pipeline regardless of how the user selected a component
(canvas click, Layers panel, drop-zone parent, or inner chrome child):

```
component:selected
  → findInspectableRoot(component, editor)     // climb to data-voodbuilder-block root
  → resolveSettings(component, editor)         // match descriptor via blockIds / matchBlockId
  → shouldPromoteSelectionToRoot(raw, root)    // re-select block root when needed
  → ensureRootInspectable(root)                // restore selectable/layerable on block root
  → descriptor.render({ mount, root, editor }) // custom form in .voodbuilder-editor-site-chrome-settings-mount
```

### Resolution rules (`findInspectableRoot`)

1. Layout **content slot** → no root (show slot hint in Content tab).
2. Selected node **is** a block root (`data-voodbuilder-block`) → use it.
3. Selected node is a **chrome drop zone** → first `[data-voodbuilder-block]` descendant.
4. Otherwise climb parents / search subtree for the nearest block root.

Promotion and settings resolution **do not** depend on `selectable: true`. Layer filters
(`layers-chrome-filter.js`) may mark chrome shell children non-selectable; block roots in
layout mode are always re-enabled via `ensureRootInspectable()` and a dedicated early exit
in the layer filter.

### `layoutOnly` descriptors

Set `layoutOnly: true` on descriptors that must appear only in the chrome **layout** editor
(nav/footer). The page shell editor keeps chrome blocks read-only and falls back to default
traits without custom settings.

When a settings descriptor matches a **newly selected** block root (by block id),
the Content tab opens once. The user can then freely switch to Style / Dynamic /
Conditions / Layers — switching tabs must never force Content again. After dynamic
refresh the auto-tab key is cleared so the next selection can reopen Content once.

### TraitManager guard

When a descriptor matches, `registerSettingsUi()` wraps `TraitManager.select()` so Editor
does not overwrite the custom mount with default Id/Title traits.

## PHP contract

Server blocks may implement `EditorConfigurableBlock` to document normalized config.
The JS registry is the runtime source of truth for inspector UI.
