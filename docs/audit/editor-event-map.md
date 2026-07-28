# Editor event map

## GrapesJS built-ins heavily used

| Event | Typical consumers | Notes |
|---|---|---|
| `load` | init, chrome, bindings, templates, animations, nesting | Fired once per editor; many handlers |
| `canvas:frame:load` | chrome CSS inject, tailwind rebuild, dynamic refresh | Re-fired on frame reload; duplicate bind risk |
| `component:add` / `remove` | nesting guard, templates, plugin refresh, dirty notify | Hot path |
| `component:selected` | style inspector, animation sector | |
| `component:styleUpdate` / `style:change` | visual style, dirty notify | |
| `component:update` / `component:update:classes` | class suggestions, animation | |
| `block:drag:start/stop` | nesting, drag lock, tailwind | |
| `sorter:drag:start/end` | nesting, drag lock | |
| `rte:enable` / `rte:disable` | text elements | |
| `component:deselected` | text elements | |
| `update` | dirty / build status | |
| `block:add` / `block:remove` | page templates sidebar | |

## Custom VoodBuilder events

| Event | Purpose |
|---|---|
| `voodbuilder:refresh-dynamic-block` | Re-render dynamic block preview |
| `voodbuilder:chrome-layout-ready` | Layout chrome ready; invalidate CSS |
| `voodbuilder:page-css-compiled` | After Tailwind page compile |
| `voodbuilder:page-css-invalidate` | Force rebuild |

## Multi-listener hotspots

`editor/init.js` alone registers multiple `load` and `canvas:frame:load` handlers. Same events also registered in `page-tailwind-autobuild.js`, `plugins/voodbuilder-grapesjs.js`, `section-*`, `style-animation-sector.js`, etc.

**Phase 5 task:** centralise subscription via an event bus/registry and assert single registration in Vitest.

## Persistence / export hooks

Export path synchronises bindings, conditions, spacing/paint, video, chrome spacers before save payload (`buildPayload` in `editor/payload.js`). Event order vs export must stay stable.
