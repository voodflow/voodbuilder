---
title: PHP SDK — Content channels
description: Register route areas for chrome and search.
---

# PHP SDK — Content channels

Content channels tell VoodBuilder which routes belong to a product area (docs, blog, …) so chrome layouts and search can target them.

## From a package

```php
Voodbuilder::contentChannel('acme', [
    'label' => 'Acme',
    'routes' => ['acme.*'],
    'sub_theme' => 'site',
    'search' => AcmePost::class, // optional
]);
```

Or implement `PublicContentChannel`.

## From the host (survives updates)

`config/voodbuilder-integrations.php`:

```php
return [
    'content_channels' => [
        'blog' => [
            'label' => 'Blog',
            'routes' => ['blog.*'],
        ],
    ],
];
```

| Approach | Survives Composer update? |
|----------|---------------------------|
| `voodbuilder-integrations.php` | Yes |
| `config/voodbuilder.php` channels | Yes |
| `AppServiceProvider` registration | Yes |
| Edit vendor ServiceProvider | **No** |

Chrome assignment: [user chrome layouts](../../user/admin/chrome-layouts).
