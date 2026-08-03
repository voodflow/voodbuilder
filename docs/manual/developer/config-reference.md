---
title: Config reference
description: Key options in config/voodbuilder.php.
---

# Config reference

Primary file: `config/voodbuilder.php`. Highlights:

## Modules

```php
'modules' => [
    'history' => ['enabled' => true],
    'conditions' => ['enabled' => true],
    'templates' => ['enabled' => true],
    'themes' => ['enabled' => true],
    'menus' => ['enabled' => true],
    'layouts' => ['enabled' => true],
    'pages' => ['enabled' => true],
    // companion-oriented flags may also appear here
],
```

## License / edition

```php
'license' => [
    'edition' => env('VOODBUILDER_EDITION', 'community'),
    'driver' => env('VOODBUILDER_LICENSE_DRIVER', 'config'),
    // anystack remote options…
],
```

Gate features with `Voodbuilder::can('…')` — do not hard-code plan names in plugins.

## Editor plugins

```php
'grapesjs' => [ // internal config key — not a public product name
    'plugins' => [/* forms, tabs, custom-code, style-bg */],
    'image_editor' => true,
],
```

## Editor / Tailblocks / fonts / layouts

See inline comments in the published config for `editor.tailblocks`, layout view names, marketing URL, revision limits, and font settings.

## Integrations file

`config/voodbuilder-integrations.php` (host-owned) registers content channels that must survive Composer updates — see [Content channels](./php-sdk/content-channels).
