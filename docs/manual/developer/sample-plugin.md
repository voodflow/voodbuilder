---
title: Sample plugin
description: Minimal Acme plugin used in tests and as a template.
---

# Sample plugin

Core ships a minimal third-party style example:

- `tests/Fixtures/SamplePlugin/SampleAcmePlugin.php`
- `tests/Architecture/SampleThirdPartyPluginTest.php`

It registers:

1. A static `editorBlock`  
2. An `editorBindingSource`  
3. An `editorCondition`

## Copy-paste starter

```php
final class SampleAcmePlugin
{
    public static function register(): void
    {
        Voodbuilder::editorBlock(
            id: 'acme-hello',
            label: 'Acme Hello',
            category: 'Acme',
            content: '<div class="p-4" data-acme-hello="1">Hello from Acme</div>',
        );

        // + binding source + condition — see the fixture file
    }
}
```

Call `SampleAcmePlugin::register()` from your package `boot()`.

## Next steps for a real companion

1. Add a `VoodBuilderModule` if you need routes/resources  
2. Ship `resources/js/editor/plugin.js` for UI  
3. Gate Pro surfaces with `Voodbuilder::can(...)`  
4. Document your companion in its own VitePress section (later manuals)
