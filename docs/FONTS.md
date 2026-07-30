# Font manager

Self-hosted webfonts for the Editor Style Manager and published pages. **No Google Fonts CDN** in Core (privacy / GDPR).

## Architecture

| Layer | Role |
|-------|------|
| `resources/fonts/core-catalog.json` | ~50 Fontsource fonts (sans / serif / mono / display) |
| `FontCatalog` (PHP) | Registry + detection of used fonts in HTML/CSS |
| `FontStylesheets` | Maps font ids → built CSS URLs (`public/build/voodbuilder-fonts-manifest.json`) |
| Editor JS (`resources/js/editor/fonts/`) | Catalog, lazy Fontsource loaders, Style Manager options |
| Vite plugin (`bin/voodbuilder-fonts-vite-plugin.js`) | Publish entries + fonts manifest on `vite build` |

Core loads fonts **on demand** in the editor (dynamic `import()`). Published pages load **only fonts referenced** in the page payload via `<link>` tags.

## Extending with plugins (e.g. Bunny)

The catalog is designed to grow without forking Core.

### PHP (ServiceProvider)

```php
use Voodflow\Voodbuilder\Support\Fonts\FontDefinition;
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::registerFonts([
    [
        'id' => 'bunny-something',
        'family' => 'Something',
        'category' => 'sans-serif',
        'provider' => 'bunny',
        'package' => null,
        'files' => [],
        'stack' => '"Something", ui-sans-serif, system-ui, sans-serif',
        'weights' => [400, 700],
        'meta' => ['bunny_slug' => 'something'],
    ],
]);

// Optional: server-side publish hook for non-fontsource providers
Voodbuilder::fonts()->registerProviderLoader('bunny', function (FontDefinition $font): void {
    // e.g. enqueue self-hosted Bunny CSS, or write into fonts manifest
});
```

### JS (companion `plugin.js` or `window.VoodbuilderEditor`)

```js
window.VoodbuilderEditor.registerFonts([
    {
        id: 'bunny-something',
        family: 'Something',
        provider: 'bunny',
        stack: '"Something", ui-sans-serif, system-ui, sans-serif',
        load: async (font) => {
            // inject <link> or fetch self-hosted CSS into editor + canvas
        },
    },
]);

// Or a shared loader for all fonts of a provider:
window.VoodbuilderEditor.registerFontProvider('bunny', async (font) => {
    // ...
});
```

Path-repo companions can also ship `voodbuilder-fonts/resources/js/editor/plugin.js` — discovered by `plugin-bridge.js` via `import.meta.glob`.

**Contract:** plugins must not patch GrapesJS core. Prefer `registerFonts` / `registerFontProvider` and self-hosted assets (or a CDN only if the product policy allows it for that plugin).

## Editor flow

1. `EditorGate::config()` exposes `fonts` → `FontCatalog::toEditorPayload()`.
2. `registerFontsUi()` boots the catalog, fills Style Manager `font-family` options, watches changes.
3. On change, `ensureFontLoaded()` lazy-imports Fontsource CSS into host + canvas.
4. On save, `EditorGate::normalizePayload()` runs `FontStylesheets::withDetectedFonts()` and persists `builder_payload.fonts`.

## Publish flow

1. `vite build` generates per-font entries under `resources/js/fonts/publish/` and writes `public/build/voodbuilder-fonts-manifest.json`.
2. `site-page.blade.php` resolves URLs with `FontStylesheets::urlsFor($page->builder_payload['fonts'])` (fallback: detect from CSS/HTML).
3. Only those stylesheets are linked in `<head>`.

## Host npm deps

Fontsource packages are listed from the catalog via `ConfigureNpmForVoodbuilder::fontsourcePackagesFromCatalog()`.

```bash
php artisan voodbuilder:sync-npm-deps
npm install
npm run build
```

Regenerate editor loaders after catalog edits:

```bash
node packages/voodflow/voodbuilder/bin/generate-fontsource-loaders.js
```

## Related

- `docs/EDITOR_JS_PLUGINS.md` — companion plugin bridge
- `docs/piano-intervento-voodbuilder.md` §2.7
