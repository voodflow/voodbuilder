---
title: PHP SDK — Menu item types
description: Custom navigation item types for Filament menus.
---

# PHP SDK — Menu item types

```php
Voodbuilder::menuItemType('acme_doc', [
    'label' => 'Acme doc',
    'allows_root' => true,
    'allows_child' => true,
    'form' => fn () => [ /* Filament fields */ ],
    'resolve_url' => fn ($item) => route('acme.show', $item->meta['slug']),
    'is_active' => fn ($item) => request()->routeIs('acme.show'),
]);
```

Or implement `MenuItemTypeHandler`.

Use this when menu nodes must pick plugin-owned records (documentation trees, course modules, …) instead of raw URLs.
