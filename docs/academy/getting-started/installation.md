---
title: Installation
description: Install VoodBuilder core in a Laravel + Filament host application.
---

# Installation

VoodBuilder core is **free and open source (MIT)**. Install it like any Laravel package.

## Requirements

- PHP 8.4+
- Laravel 12 or 13
- Filament 5
- Node.js for frontend assets (`npm run build` or `npm run dev`)

## 1. Require the package

```bash
composer require voodflow/voodbuilder
```

If you use a private Composer repository or path repository, follow your organisation’s feed instructions — the package name stays `voodflow/voodbuilder`.

## 2. Run the installer

```bash
php artisan voodbuilder:install
```

The installer typically:

- Publishes `config/voodbuilder.php`
- Runs database migrations
- Publishes Spatie Media Library config and the media table migration
- Attempts to patch `vite.config.js` with editor entry points

Use `--force` if you need to overwrite published config files.

## 3. Register the Filament plugin

In your panel provider:

```php
use Voodflow\Voodbuilder\VoodbuilderPlugin;

$panel->plugins([
    VoodbuilderPlugin::make(),
]);
```

## 4. Build frontend assets

```bash
npm install
npm run build
```

For local development, `npm run dev` keeps the editor assets hot-reloaded.

## 5. Authorize admin users

Ensure your Filament users can access Site Pages, Menus, Layouts, and Settings according to your policies. Editor routes (`?edit=1`) require authentication and appropriate permissions.

## Companion packages (later)

Paid companions (Dynamics, Components, Templates Pro, Popups, …) install with their own Composer package and licence activation. This academy documents what each companion unlocks; licensing steps will be added when the commercial flow is finalised.

::: tip Verify the install
After setup, open **Admin → Site → Pages**, create a page with **Visual builder**, and use **Open visual editor**. If the three-column editor loads, installation succeeded.
:::

Next: [Interface tour](./interface-tour)
