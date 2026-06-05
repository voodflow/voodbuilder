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

The install command publishes Spatie Settings (required for cookie consent), the Laravel **notifications** table (required for Filament + comment bell), SEO config/migrations, Vpress config, runs `migrate`, and seeds default navigation and cookie policy pages.

> **Note:** Vpress loads its own migrations from the package. You do not need to publish Vpress migrations manually — doing so creates duplicate migration files.

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
- **Settings → Cookie consent** — configure the public-site banner (banner does **not** appear in admin)

## With voodflow/tutorials

Install both packages, then:

```bash
php artisan vpress:install   # configures tutorials layouts + cookie consent + migrate
php artisan tutorials:install
```

`vpress:install` sets in `config/tutorials.php`:

- `layout` → `vpress::layouts.page`
- `doc_layout` → `vpress::layouts.doc`
- `localization.fallback_url` for the language switcher

Tutorial pages use the vpress shell (nav, footer, cookie banner, Vite theme). Tutorial-specific styles (comments, TOC, materials) still load from `tutorials.css`.

The main nav seeder adds a **Tutorials** link when `tutorials.index` exists.

### Cookie consent

- **Public frontend** — banner in `vpress::layouts.app` (head + body includes)
- **Filament admin** — settings page only; `vpress:install` adds `dont-discover` for `filament-cookie-consent` so the banner is not injected into the panel

After install, run `composer dump-autoload` if the admin still shows the banner.

## Vite, CSS e font

Vpress non serve CSS precompilato: il tema va **bundlato con Vite** nell’app host, come `resources/css/app.css`. Il file `theme.css` del package è solo un **entry point** da registrare in configurazione — **non** un comando da eseguire nel terminale.

### Passo 1 — dipendenze npm

```bash
npm install -D @fontsource-variable/inter @fontsource/jetbrains-mono tailwindcss @tailwindcss/vite
```

### Passo 2 — entry in `vite.config.js`

Apri `vite.config.js` e aggiungi il path del tema nell’array `input` del plugin Laravel (path repo locale **oppure** vendor dopo install Composer):

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Sviluppo con path repo:
                'packages/voodflow/vpress/resources/css/theme.css',
                // Oppure, se il package è solo in vendor:
                // 'vendor/voodflow/vpress/resources/css/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(), // richiesto: theme.css usa @import 'tailwindcss'
    ],
});
```

| Cosa | Dove |
|------|------|
| `npm install …` | Terminale — comando bash |
| `'packages/.../theme.css'` | `vite.config.js` → array `input` — **stringa**, non comando |
| `npm run build` | Terminale — compila gli entry e scrive in `public/build/` |

`config/vpress.php` elenca gli stessi path in `assets.vite` così il layout Blade sa cosa caricare con `@vite(...)` — devono coincidere con `vite.config.js`.

### Passo 3 — build

```bash
npm run build
```

In sviluppo: `npm run dev` (Vite in watch).

### Errore comune

Se incolli solo la riga del path nel terminale:

```bash
'packages/voodflow/vpress/resources/css/theme.css'
# bash: Permission denied  ← non è un eseguibile, va in vite.config.js
```

### Dove eseguire npm

| Comando | Dove |
|---------|------|
| `composer`, `php artisan` | Container Docker (`docker compose exec app …`) |
| `npm install`, `npm run build`, `npm run dev` | **Mac host** (cartella `app/`), non nel container PHP |

Il container è Linux; Vite 8 installa binding nativi diversi da macOS (`darwin-arm64` vs `linux-arm64`). Il volume `./app` è condiviso: la build fatta sul Mac finisce in `public/build/` e il container la serve subito.

### Rolldown / Vite 8 (solo su Mac)

Se `npm run build` sul **Mac** fallisce con `@rolldown/binding-darwin-arm64`:

```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

Non aggiungere `@rolldown/binding-darwin-arm64` a `package.json`: rompe `npm install` dentro Docker/Linux.

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
| `notifications.enabled` | Frontend notification bell (requires `notifications` DB table) |
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
