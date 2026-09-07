---
title: PHP SDK — Fonts
description: Extend the self-hosted font catalog.
---

# PHP SDK — Fonts

Core ships a Fontsource-based catalog (~50 families) with Style Manager search and publish-only-used-fonts behaviour.

## Register fonts / providers

```php
Voodbuilder::registerFonts([/* FontDefinition instances */]);
```

JS companions can call `registerFonts` / `registerFontProvider` through the plugin bridge (see package `docs/FONTS.md`).

Do not hotlink arbitrary Google Fonts CSS in blocks if you want offline-friendly, CSP-safe publishing — prefer the catalog pipeline.
