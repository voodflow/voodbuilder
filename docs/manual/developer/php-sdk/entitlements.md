---
title: PHP SDK — Entitlements
description: Gate features by edition without hard-coding plans.
---

# PHP SDK — Entitlements

```php
if (Voodbuilder::can('dynamic-data.collections')) {
    // enable Pro surface
}

if (Voodbuilder::cannot('components.library')) {
    // show soft-gate
}
```

Edition comes from `config('voodbuilder.license.edition')` (or remote driver). The capability matrix lives in Core licensing classes.

**Plugins should check capabilities**, not string-compare `community` / `professional` / `agency`.
