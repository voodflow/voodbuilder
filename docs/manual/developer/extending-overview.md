---
title: Extending overview
description: How third-party plugins add features without forking.
---

# Extending overview

Third parties add functionality by **registration**, not by editing Core.

## PHP (ServiceProvider)

```php
use Voodflow\Voodbuilder\Voodbuilder;

public function boot(): void
{
    Voodbuilder::editorBlock(
        id: 'acme-hello',
        label: 'Acme Hello',
        category: 'Acme',
        content: '<div class="p-4">Hello</div>',
    );

    Voodbuilder::editorCondition('acme_feature_flag', function (array $condition, $page): bool {
        return ($condition['value'] ?? null) === 'on';
    });

    Voodbuilder::contentChannel('acme', [
        'label' => 'Acme',
        'routes' => ['acme.*'],
    ]);
}
```

## What you can register

| API | Purpose |
|-----|---------|
| `editorBlock` | Static HTML sidebar block |
| `editorRichContentBlock` | Filament RichEditor block → editor placeholder |
| `editorServerBlock` | Server-rendered block (`EditorServerBlock`) |
| `editorBindingSource` | Dynamic field source |
| `editorRepeatList` | List repeat (no-op without Dynamics package) |
| `editorCondition` | Custom visibility evaluator |
| `editorLabels` | Extra editor UI strings |
| `contentChannel` | Route areas for chrome / search |
| `menuItemType` | Custom menu item types |
| `richContentBlock` | TipTap admin blocks |
| `subTheme` | Visual theme definition |
| `registerFonts` / font providers | Webfont catalog |
| `registerModule` | Full module lifecycle (companions) |
| `can` / `cannot` | Entitlement checks |

## JavaScript

```js
window.VoodbuilderEditor.registerPlugin({
  id: 'acme-editor',
  mount(editor, context) {
    // context.entitlements, context.urls, context.labels, …
  },
});
```

Or ship `resources/js/editor/plugin.js` for path-install discovery via `import.meta.glob`.

Details: [JS plugins](./js-plugins).

## Host persistence

Prefer host `config/voodbuilder-integrations.php` or `AppServiceProvider` over editing vendor packages.

## Do not

- Patch the canvas engine under `node_modules`  
- Fork Core for a single block  
- Hard-code edition names — use entitlements  
- Put paid UI into Core — use companions + soft-gates  

Walkthrough: [Sample plugin](./sample-plugin).
