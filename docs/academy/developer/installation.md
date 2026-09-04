---
title: Installation
description: Wire VoodBuilder into a Laravel + Filament host application.
---

# Installation (host app)

## Composer

```bash
composer require voodflow/voodbuilder
php artisan voodbuilder:install
```

Publish options:

```bash
php artisan vendor:publish --tag=voodbuilder-config
php artisan migrate
```

## Filament plugin

```php
use Voodflow\Voodbuilder\VoodbuilderPlugin;

$panel->plugins([
    VoodbuilderPlugin::make(),
]);
```

## Frontend assets

Ensure Vite includes VoodBuilder editor entries (the installer attempts to patch `vite.config.js`):

```bash
npm install
npm run build
```

See the package `docs/BUILD.md` for engineering details on theme CSS and section utilities.

## Policies

Authorize models such as `SitePage`, chrome layouts, and menus with your host policies. Editor routes require authentication and throttling.

## Configuration

Host apps typically publish `config/voodbuilder.php` and optionally `config/voodbuilder-integrations.php` for third-party registrations.

Prefer registering blocks and bindings from your `AppServiceProvider` or a dedicated service provider — not by editing vendor files.

## Media library

The installer publishes Spatie Media Library config. Reusable media uses the singleton `MediaLibrary` model with `images` and `videos` collections.

Next: [Block authoring](./block-authoring)
