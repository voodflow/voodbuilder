---
title: Registering blocks
description: Add blocks and binding sources from PHP and JavaScript.
---

# Registering blocks

Third parties extend VoodBuilder by **registration** in a service provider — never by editing core.

## Static sidebar block (core)

```php
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::editorBlock(
    id: 'acme-hello',
    label: 'Acme Hello',
    category: 'Acme',
    content: '<div class="rounded-2xl bg-vp-surface p-6 text-vp-fg">Hello</div>',
);
```

Follow [Block authoring](./block-authoring) markup rules so the block is droppable and theme-aware.

## Server-rendered block (core)

For blocks that need PHP at render time, register an `EditorServerBlock` — useful for data that must not be stored only as static HTML.

## Binding source (core API, Dynamics for model UI)

Register field sources for **Make dynamic**:

```php
use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingField;
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::editorBindingSource(new class implements EditorBindingSource {
    public function id(): string { return 'acme.site'; }
    public function label(): string { return 'Acme site'; }
    public function package(): string { return 'acme'; }
    public function packageLabel(): string { return 'Acme'; }

    public function fields(): array
    {
        return [
            new BindingField('tagline', 'Tagline', BindingField::TYPE_TEXT),
        ];
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        return $fieldId === 'tagline' ? config('acme.tagline') : null;
    }
});
```

The **Model Integrations** admin UI and list repeat product features ship with the **Dynamics** companion. Core keeps contracts and public render resolution.

## List repeat (Dynamics)

`Voodbuilder::editorRepeatList(...)` registers collection queries. It is a **no-op** without the Dynamics package — ship list UI in that companion.

## Other registrations (core)

| API | Purpose |
|-----|---------|
| `editorCondition()` | Custom visibility rules |
| `contentChannel()` | Route areas for chrome / search |
| `menuItemType()` | Custom menu item types |
| `subTheme()` | Visual theme definitions |
| `registerFonts()` | Webfont catalog entries |
| `registerModule()` | Full companion module lifecycle |

## JavaScript plugin (core)

```js
window.VoodbuilderEditor.registerPlugin({
  id: 'acme-editor',
  mount(editor, context) {
    // context.entitlements, context.urls, context.labels, …
  },
});
```

Or ship `resources/js/editor/plugin.js` for automatic discovery via the host Vite glob.

## Do not

- Patch GrapesJS under `node_modules`
- Fork core for a single marketing section
- Hard-code edition names — use entitlements in companions
- Put paid-only UI into core — soft-gate and ship the companion

Host persistence tip: prefer `config/voodbuilder-integrations.php` or your package service provider over editing published vendor files.

Back to: [Developer overview](./index)
