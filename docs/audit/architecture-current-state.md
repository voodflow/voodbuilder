# Architecture — current state

**Audited version line:** `0.0.11` (`composer.json`)  
**Branch:** `refactor/modular-architecture`

## What VoodBuilder is today

A single Composer package (`voodflow/voodbuilder`) that provides:

- Filament admin resources (pages, menus, chrome layouts, model integrations, popups, settings)
- Public Laravel routes (home, localized pages, search, auth, account)
- GrapesJS visual editor (page / chrome-layout / popup modes) bundled via host Vite
- Theme/sub-theme system + React theme-map bundle (esbuild)
- Dynamic bindings, conditions, components, page templates, revisions, Tailwind JIT helpers

## Monolith shape

```text
VoodbuilderServiceProvider
  ├─ packageRegistered: many singletons (blocks, bindings, themes, menus, …)
  ├─ packageBooted: Livewire, assets, GrapesJS routes/blocks/bindings, SEO, rich content
  └─ discovers/runs migrations from package

VoodbuilderPlugin (Filament)
  └─ registers resources/pages behind config flags

resources/js/grapesjs/editor/init.js
  └─ imports and wires nearly all editor features in one bootstrap
```

There is **no** `ModuleRegistry`. Optional features use `config('voodbuilder.*.enabled')` and direct imports.

## Existing contracts (partial)

Under `src/Contracts/`:

- `GrapesJsBindingSource`
- `GrapesJsConfigurableBlock`
- `GrapesJsServerBlock`
- `MenuItemTypeHandler`
- `PublicContentChannel`

These are feature-level contracts, not module lifecycle contracts.

## Licensing today

`Support/License/VoodbuilderLicense` validates a key when `config('voodbuilder.license.enforce')` is true. There is **no** capability matrix, plan resolver, or AnyStack adapter.

## GrapesJS policy (already correct)

No patches under `node_modules/grapesjs`. Extension is via plugins, commands, traits, events, CSS, and PHP renderers.

## JS modularisation status

Partial: folders `core/`, `chrome/`, `blocks/`, `editor/`, `_legacy/` exist (see `docs/JS_MODULAR_ARCHITECTURE.md`). Bootstrap remains monolithic; largest leaf modules include `bindings-ui.js`, `components-ui.js`, `popups-ui.js`, `editor/init.js`, `tailwind-visual-style.js`.

Aggregate GrapesJS package JS (non-generated): ~1.8MB source across ~145 files (not a single 2.5MB file anymore, but still one behavioural unit at runtime).

## Risk summary

Highest risk areas for modular extraction: editor bootstrap order, public HTML pipeline, popup/components assumed present, and 16 failing PHPUnit tests at freeze (see risk-register).
