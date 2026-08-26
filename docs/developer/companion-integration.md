# Companion & third-party integration

How **companion packages** and the **host application** extend VoodBuilder core.

## Principles

1. **Registration only** — never edit vendor/core files for product features
2. **Soft gates** — core boots without companions; missing packs show upsell/empty states
3. **Vanilla GrapesJS** — no patches under `node_modules`; pin version in host `package.json`
4. **Public APIs** — `Voodbuilder` facade + `Contracts\*` + JS plugin bridge

## Module lifecycle

Companions implement `Voodflow\Voodbuilder\Contracts\VoodBuilderModule` and register early:

```php
use Voodflow\Voodbuilder\Voodbuilder;

public function packageRegistered(): void
{
    $this->app->booting(function (): void {
        if (! class_exists(Voodbuilder::class)) {
            return;
        }

        Voodbuilder::registerModule(new FormsModule, enabled: true);
    });
}
```

`ModuleRegistry` boots modules after core is ready. Prefer `Application::booting` so registration wins the race against `ModuleRegistry::boot()`.

Reference companions in this monorepo: `vforms`, `vpopups`, `vcookiebar`, `voodbuilder-components`, `voodbuilder-dynamic-data`, `voodbuilder-templates`, `voodbuilder-elements`.

## PHP registration surfaces

| API | Typical companion use |
|-----|------------------------|
| `editorBlock` | Static HTML sidebar blocks |
| `editorRichContentBlock` | Filament RichEditor → canvas placeholder |
| `editorServerBlock` | Server-rendered block without RichEditor |
| `editorBindingSource` | Dynamic field sources |
| `dynamicPageProvider` | Claim companion routes with a SitePage template ([dynamic-site-pages.md](./dynamic-site-pages.md)) |
| `editorRepeatList` | List repeat (no-op without Dynamics package) |
| `editorCondition` | Visibility evaluators |
| `editorLabels` | i18n strings in editor bootstrap |
| `contentChannel` | Route areas for chrome / search |
| `menuItemType` | Custom nav item types (e.g. docs) |
| `subTheme` / fonts | Visual themes |
| `can` / `cannot` | Entitlement checks for paid UI |

Full walkthrough: [Extending overview](../manual/developer/extending-overview.md), [Sample plugin](../manual/developer/sample-plugin.md).

## JavaScript plugins

```js
window.VoodbuilderEditor.registerPlugin({
  id: 'acme-editor',
  mount(editor, context) {
    // context.entitlements, context.urls, context.labels
  },
});
```

Path-install discovery: ship `resources/js/editor/plugin.js` for `import.meta.glob` pickup. See [JS plugins](../manual/developer/js-plugins.md) and [EDITOR_JS_PLUGINS.md](../EDITOR_JS_PLUGINS.md).

> **Content width / section layout — do not put in the right settings panel.**  
> Companions must **not** expose Section width, Section padding, or Content width as GrapesJS sidebar selects (nor as Filament fields that only exist for that panel). Width is owned by the canvas **content-width** toolbar icon on `section.voodbuilder-editor-section` / `.voodbuilder-editor-container[data-voodbuilder-role="content"]`. Use the standard markup contract in [CONTENT_WIDTH.md](../CONTENT_WIDTH.md) and [EDITOR_BLOCK_AUTHORING.md](../EDITOR_BLOCK_AUTHORING.md).  
> Do **not** set `data-voodbuilder-layout="container"` on companion content shells — that attribute is only for Layout builder Columns / layout picker.

### Where custom editor code lives

| Allowed | Forbidden |
|---------|-----------|
| `src/Support/Editor/**` (core) | `node_modules/grapesjs/**` |
| `resources/js/editor/**` (core or companion) | Patching other editor npm packages |
| Companion package JS + published assets | Forking core for one trait |

After `npm update` on GrapesJS: smoke-test `?edit=1` (blocks, traits, save/reload). See [EDITOR.md](../EDITOR.md) → *Surviving GrapesJS upgrades*.

## Working with other plugins

| Peer | How they meet |
|------|----------------|
| **Vforms** | Registers `FormsModule` + editor blocks; injects runtime JS/CSS on site layouts via View composers; appends canvas styles without core `@import` |
| **Voodflow** | Optional; not required for builder. Forms/automation companions may register nodes separately |
| **vmedia** | **Required** free companion: vault + galleries + Editor media browser. Without `mediaGalleriesUrl`, the editor falls back to GrapesJS Asset Manager (upload/URL only) and will not apply alt/caption/credits |
| **vpopups / vcookiebar** | Modules + blocks / content channels |
| **Dynamics / Components / Templates** | Soft-gated registries (`RepeatListRegistry`, etc.) — APIs no-op or upsell when package absent |

### Media browser contract (vmedia)

- Register `VmediaPlugin` on the Filament panel and keep `VMEDIA_ENABLED=true`.
- Editor config must expose both `mediaLibraryUrl` and `mediaGalleriesUrl` (routes `vmedia.media.index` + `vmedia.media.galleries`).
- Choosing an asset applies `alt`, `caption`, `credits`, `name` / `file_name` onto the canvas (`alt` + `data-vb-media-*`).
- Do not open GrapesJS Asset Manager when vmedia is active — UI entry points go through `openMediaAssets()`.

Host persistence: prefer `config/voodbuilder-integrations.php` or `AppServiceProvider` over editing vendor packages.

## Filament plugin

```php
->plugins([
    \Voodflow\Voodbuilder\VoodbuilderPlugin::make(),
])
```

Admin resources/pages live under the Site / Builder navigation groups defined by the plugin.

## Config & publishing

- `config/voodbuilder.php` — editor plugins, canvas styles, routes, entitlements hooks
- `php artisan voodbuilder:install` — migrations, npm sync, Vite patch
- Publish config/views only when the host must override

## Events

- Laravel: e.g. `EditorFormSubmitted` (forms companion submissions)
- JS bus: `voodbuilder:*` events — see JS plugins docs
- Prefer listening/registering over calling private editor HTTP internals from Blade

## Security notes for companions

- Editor HTTP routes under `voodbuilder/editor/*` are authenticated + throttled — treat as privileged
- Do not expose entitlement bypasses from public pages
- Sanitize any server-rendered block HTML; escape attribute output
- Media uploads follow core media policies — do not weaken MIME/extension checks in companions

## Testing

- Characterisation / unit coverage under package `tests/` where present
- Manual smoke: publish page → `?edit=1` → insert companion block → save → public render
- Soft-gate: remove companion from Composer and confirm core still boots with upsell UI
