# Editor command map

Commands registered via `editor.Commands.add` / `commands.add`:

| Command constant / id | File | Purpose |
|---|---|---|
| `CMD_PREVIEW` | `editor-chrome.js` | Preview toggle |
| `CMD_DEVICE_DESKTOP` | `editor-chrome.js` | Desktop device |
| `CMD_DEVICE_TABLET` | `editor-chrome.js` | Tablet device |
| `CMD_DEVICE_MOBILE` | `editor-chrome.js` | Mobile device |
| `CMD_LAYOUT_PICKER` | `layout-blocks.js` | Layout picker |
| `voodbuilder:edit-code` | `editor-code-block.js` | Code block editor |
| `CMD_EDIT_BLOCK_CODE` | `canvas-block-code-editor.js` | Edit block source |
| `CMD_EDIT_IMAGE` | `jodit-image-editor.js` | Image editor |
| `CMD_COPY_COMPONENT_CLASSES` | `canvas-component-toolbar.js` | Copy classes |
| `CMD_COPY_COMPONENT_CODE` | `canvas-component-toolbar.js` | Copy code |
| `CMD_MAKE_DYNAMIC` | `bindings-ui.js` | Bind dynamic data |
| `CMD_CLEAR_DYNAMIC` | `bindings-ui.js` | Clear binding |
| `CMD_CYCLE_CONTENT_WIDTH` | `content-width-toolbar.js` | Cycle content width |

## Gaps vs target registry

There is no central command registry module. Commands are side-effect imports from `editor/init.js`.

**Phase 5:** introduce `registerEditorCommands(editor, context)` per module; Core lists enabled commands from entitlement + module contributions.
