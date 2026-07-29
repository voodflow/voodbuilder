# Phase 5 — Editor registries

## Work completed

Introduced JS editor contribution registries with a compatibility bridge:

- `resources/js/editor/editor/registries/{commands,panels,data-sources,conditions}.js`
- `resources/js/editor/editor/compatibility-bridge.js` (`@deprecated remove-by 0.2.0`)
- `bootEditorRegistries()` hooked after `grapesjs.init` in `editor/init.js`
- PHP contracts: `RegistersEditorCommands`, `RegistersEditorPanels`

Legacy `Commands.add` call sites remain unchanged; bridge is additive.

## Tests

Vitest: `editor/registries` command + panel filtering cases.

## Acceptance

Same editor behaviour; registries ready for gradual command/panel migration.
