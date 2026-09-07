---
title: Events & hooks
description: Laravel events and editor extension hooks.
---

# Events & hooks

## Laravel

- `EditorFormSubmitted` — form plugin submissions (listen in the host or a forms companion)

## EditorGate label providers

```php
Voodbuilder::editorLabels(fn (): array => [
    'acmeHello' => __('acme::ui.hello'),
]);
```

Strings merge into the editor bootstrap payload.

## HTTP surfaces

Authenticated, throttled routes under `voodbuilder/editor/*` (blocks, bindings, media, compile-css, revisions, page-templates, chrome-layout, …). Treat them as de-facto editor API; prefer SDK registration over calling internals from Blade.

## JS event bus

See [JS plugins](./js-plugins) for `voodbuilder:*` event names.
