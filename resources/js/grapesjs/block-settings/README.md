# Block settings module

Generic inspector settings for GrapesJS blocks that expose custom configuration UI
beyond default GrapesJS traits.

## Design goals

- **One resolution path** for every editor mode (page, layout, popup).
- **Atomic helpers** instead of per-block selection hacks.
- **Declarative descriptors** — blocks opt in with `blockIds` / `matchBlockId`.
- **No GrapesJS core patches** — integration uses public APIs and DOM mounts only.

## Architecture

```
block-settings/
├── selection.js   # Resolve `[data-voodbuilder-block]` roots from any selection
├── registry.js    # Descriptor map + normalization
├── ui.js          # Inspector mount + GrapesJS event wiring
├── index.js       # Public API
└── README.md
```

## Registering a block

```js
import { registerBlockSettings } from './block-settings/index.js';

registerBlockSettings({
    id: 'my_block',
    blockIds: ['my_block_id'],
    // Optional: blockIds: ['a'], matchBlockId: (id) => id.startsWith('prefix_'),
    render: ({ mount, root, editor }) => {
        mount.appendChild(/* custom settings UI */);
    },
});
```

Custom `findRoot` / `matchesRoot` remain supported for non-standard roots (e.g. newsletter
forms located by `data-voodbuilder-form` instead of `data-voodbuilder-block`).

## Selection algorithm

1. If the selected component is a chrome drop zone → first `[data-voodbuilder-block]` child.
2. Else climb parents until a `[data-voodbuilder-block]` attribute is found.
3. Layout content-slot markers return no root (show slot hint instead).
4. When a descriptor matches the resolved root, custom settings render even if the raw
   selection is a non-selectable wrapper or drop zone.

## PHP contract

Server blocks may implement `GrapesJsConfigurableBlock` to document normalized config.
The JS registry is the runtime source of truth for inspector UI.
