---
title: PHP SDK — Bindings
description: Register dynamic field sources for the editor.
---

# PHP SDK — Bindings

Binding sources power **Make dynamic** field pickers. Register from PHP:

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
        return $fieldId === 'tagline' ? 'Hello' : null;
    }
});
```

Field types include text, URL, and image (see `BindingField` constants).

## Image resolvers

```php
Voodbuilder::registerBindingImageResolver($modelClass, 'cover', function ($model) {
    return $model->coverUrl();
});
```

## List repeat

`Voodbuilder::editorRepeatList(...)` registers collection queries. It is a **no-op** when the Dynamic Data collections package is absent — ship the companion for Pro list UI.

::: info
Core keeps binding contracts and render resolution. Full product UX for collections is documented with the Dynamics companion.
:::
