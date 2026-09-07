---
title: PHP SDK — Sub-themes
description: Register visual themes for content areas.
---

# PHP SDK — Sub-themes

```php
Voodbuilder::subTheme('acme', [
    'label' => 'Acme',
    'description' => 'Acme marketing look',
    'layouts' => [/* optional view overrides */],
    'css' => 'resources/css/acme-theme.css',
]);
```

Artisan helpers:

- `php artisan voodbuilder:make-subtheme`
- `php artisan voodbuilder:compile-theme-assets`

Bundled themes live under the package `resources/themes/{id}/`; app themes under `resources/voodbuilder/themes/{id}/`.
