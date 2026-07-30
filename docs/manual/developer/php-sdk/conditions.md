---
title: PHP SDK — Conditions
description: Custom visibility condition evaluators.
---

# PHP SDK — Conditions

```php
Voodbuilder::editorCondition('acme_feature_flag', function (array $condition, $page): bool {
    return ($condition['value'] ?? null) === 'on';
});
```

- `$condition` is the stored rule payload from the Conditions UI  
- `$page` may be the current `SitePage` or null  
- Return `true` to show the block  

Contribute labels for the UI via `Voodbuilder::editorLabels()`.

Built-in keys (logged-in, role, locale, route, dates) ship with the Conditions module.
