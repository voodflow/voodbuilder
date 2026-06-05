# voodflow/vpress

**Version 0.0.1**

VitePress-style public frontend for Laravel and Filament 5: managed site pages, navigation, SEO, theme (light/dark), mobile nav, and extensible RichEditor blocks.

## Requirements

- PHP 8.4+
- Laravel 12+
- Filament 5+
- Vite (for theme CSS and JS)
- [ralphjsmit/laravel-seo](https://github.com/ralphjsmit/laravel-seo)

## Installation

### From GitHub (Composer)

Add the Voodflow repository to your Laravel app `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/voodflow/vpress.git"
        }
    ],
    "require": {
        "voodflow/vpress": "^0.0.1"
    }
}
```

Then run:

```bash
composer update voodflow/vpress
php artisan vpress:install
```

The install command publishes config, runs migrations, and seeds default navigation data.

### Local path (development)

```json
{
    "repositories": [
        { "type": "path", "url": "packages/voodflow/vpress" }
    ],
    "require": {
        "voodflow/vpress": "*"
    }
}
```

## Filament

Register the plugin in your panel provider:

```php
use Voodflow\Vpress\VpressPlugin;

$panel->plugins([
    VpressPlugin::make(),
]);
```

Admin features:

- **Site → Settings** — branding, SEO defaults, theme, header options
- **Site → Pages** — home and static pages with RichEditor blocks
- **Site → Navigation** — main, footer, and header menus

## Vite and fonts

Install self-hosted fonts (no CDN at runtime):

```bash
npm install -D @fontsource-variable/inter @fontsource/jetbrains-mono tailwindcss @tailwindcss/vite
```

Add the theme entry to `vite.config.js`:

```js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'packages/voodflow/vpress/resources/css/theme.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

When installed via Composer, adjust paths to:

```js
'vendor/voodflow/vpress/resources/css/theme.css',
```

Then build assets:

```bash
npm run build
```

Import mobile nav and theme helpers from your app `resources/js/app.js` if needed (see a host app using vpress for reference).

## Configuration

Publish config:

```bash
php artisan vendor:publish --tag=vpress-config
```

Key options in `config/vpress.php`:

| Key | Purpose |
|-----|---------|
| `site_title` | Fallback site title |
| `layouts.*` | Blade layouts (app, doc, page, home) |
| `home.route_enabled` | Register `/` route |
| `home.fallback_view` | View when no home page exists in DB |
| `assets.vite` | Vite entrypoints loaded by the layout |
| `notifications.enabled` | Frontend notification bell |
| `account.enabled` | Public `/account` profile page |

Site-specific branding, favicon, logo, and theme defaults are stored in the database and managed from **Filament → Settings**.

## Custom RichEditor blocks

Register blocks from your app or other Voodflow packages:

```php
use Voodflow\Vpress\Vpress;

Vpress::richContentBlock('Dynamic', LatestNewsBlock::class);
```

Built-in blocks: Hero, Features grid, Partner banner.

## Optional integrations

Other Voodflow packages (for example `voodflow/tutorials`) can:

- extend layouts via `config('vpress.layouts.doc')`
- register dynamic blocks for the page editor
- reuse nav, outline, and doc components (`<x-vpress::outline />`)

## Public routes

- `/` — home (CMS page or fallback view)
- `/pages/{slug}` — static site pages
- `/account` — user profile (when enabled)

## License

MIT — see [LICENSE](LICENSE).
