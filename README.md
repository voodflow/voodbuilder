# voodflow/vpress

**Free & Open Source (MIT)** — VitePress-style public frontend for Laravel with a **Filament 5** admin panel.

Companion plugins [voodflow/vtuts](https://github.com/voodflow/vtuts) and [voodflow/vdocs](https://github.com/voodflow/vdocs) are **paid, source-available** packages (not Open Source).

Vpress is **not a full CMS** and **requires Filament 5** for site pages, navigation, and settings. It is a **lightweight site shell**: a handful of managed pages, navigation, SEO defaults, theme (light/dark), optional auth, notifications, and layouts tuned for **documentation** (`vdocs`) and **tutorials** (`vtuts`). Think “VitePress chrome + Filament admin for site settings”, not WordPress.

## What it does

| Area | What you get |
|------|----------------|
| **Public theme** | VitePress-like nav, doc sidebar, outline scroll-spy, reading progress, mobile drawer, dark/light mode |
| **Site pages** | Home + static pages built with Filament RichEditor and custom blocks (hero, features grid, latest vtuts, …) |
| **Navigation** | Main, header-extra, and footer menus — route names, URLs, or site pages |
| **Settings (DB)** | Brand name, site title, logo, favicon, social image, theme default, locale, toggles (search, theme, language, bell) |
| **SEO** | Integrates [ralphjsmit/laravel-seo](https://github.com/ralphjsmit/laravel-seo); global defaults from Settings |
| **Auth (Fortify)** | Optional public `/login` and `/register` using **Laravel Fortify** views styled like the theme |
| **Account** | `/account` profile page (avatar, name) when enabled |
| **Notifications** | Bell in the nav for logged-in users (DB `notifications` table); e.g. new comments on your content |
| **Search** | `/search` across vtuts, vdocs, and site pages when routes exist |
| **Cookie consent** | Public banner only (admin configures policy in Filament; banner is **not** shown in the panel) |

Vpress does **not** ship blog posts, e-commerce, or arbitrary content types — pair it with **voodflow/vtuts**, **voodflow/vdocs**, or **Relaticle Ink** for that.

## Requirements

- PHP 8.4+
- Laravel 12+
- Filament 5+
- Vite + Tailwind CSS v4 (theme CSS is bundled in **your** app build)
- [ralphjsmit/laravel-seo](https://github.com/ralphjsmit/laravel-seo)
- [spatie/laravel-settings](https://github.com/spatie/laravel-settings) (site settings in DB)

**Optional**

- [laravel/fortify](https://github.com/laravel/fortify) — login/register on the public site
- [voodflow/vtuts](https://github.com/voodflow/vtuts) — tutorials with doc layout
- [voodflow/vdocs](https://github.com/voodflow/vdocs) — technical documentation

## Installation

### From GitHub (Composer)

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/voodflow/vpress.git"
        }
    ],
    "require": {
        "voodflow/vpress": "^0.0.2"
    }
}
```

```bash
composer update voodflow/vpress
php artisan vpress:install
```

`vpress:install` will:

1. Publish Spatie Settings (required for cookie consent + vpress settings)
2. Publish SEO config/migrations if needed
3. Run `migrate` (vpress tables, `notifications`, settings)
4. Seed default navigation and cookie policy page
5. Configure cookie-consent for **frontend only** (no banner in Filament)
6. If `voodflow/vtuts` is installed — patch `config/vtuts.php` to use `vpress::layouts.*`

> Vpress migrations load from the package automatically. Do not publish duplicate migration files.

### Filament

```php
use Voodflow\Vpress\VpressPlugin;

$panel->plugins([
    VpressPlugin::make(),
]);
```

**Admin → Site**

- **Settings** — branding, SEO, theme default, feature toggles, primary locale
- **Pages** — home and static pages (RichEditor + blocks)
- **Navigation** — menus linked to routes or pages

## How it works

### Layouts

| Layout | Use |
|--------|-----|
| `vpress::layouts.app` | Base shell: nav, footer, Vite assets, cookie banner |
| `vpress::layouts.home` | Home page (full-width, no doc sidebar) |
| `vpress::layouts.doc` | Doc/tutorial: fixed gray left sidebar, outline, progress bar |
| `vpress::layouts.page` | Simple content page |

Other Voodflow packages point their config at these layouts (e.g. `vtuts.doc_layout` → `vpress::layouts.doc`).

### Login & registration (Fortify)

When Fortify is installed and routes are registered, vpress serves themed `/login` and `/register` blades. Users created on the public site receive the **registered** role when Shield/vtuts integration is present. Subscriber-only content is enforced by **vtuts** visibility + SubKit, not by vpress alone.

### Notifications

Enable in **Settings** (`show_notification_bell`). Requires Laravel’s `notifications` table (`vpress:install` creates it). The bell Livewire component shows unread Filament/database notifications — useful for moderators when someone comments on a tutorial.

### Settings vs config file

- `config/vpress.php` — layouts, feature flags, Vite entry paths (committed)
- **Database** (`VpressSettings`) — logo, titles, theme default, toggles (edited in Filament)

`ApplyVpressSiteConfig` middleware applies DB settings on each web request (title, favicon, locale hints).

### Search

`/search?q=…` queries published **vtuts**, **vdocs** pages, and **site pages** when those packages/routes exist. Disable via settings if not needed.

### With voodflow/vtuts

```bash
php artisan vpress:install
php artisan vtuts:install
```

`vpress:install` sets in `config/vtuts.php`:

- `layout` → `vpress::layouts.page`
- `doc_layout` → `vpress::layouts.doc`

Tutorial listing and doc pages use the vpress shell; vtuts-specific CSS (`vtuts.css`) loads for comments, TOC, materials.

Nav seeder adds a **Tutorials** link when `vtuts.index` exists.

## Vite & CSS

Vpress does not ship pre-built CSS. Add the theme entry to **your** `vite.config.js`:

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
                'packages/voodflow/vpress/resources/css/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

Match paths in `config/vpress.php` → `assets.vite`. Then:

```bash
npm run build
```

## Custom RichEditor blocks

```php
use Voodflow\Vpress\Vpress;

Vpress::richContentBlock('Dynamic', YourBlock::class);
```

Built-in: Hero, Features grid, Partner banner. **vtuts** registers `latest_vtuts`.

## Public routes

| Route | Description |
|-------|-------------|
| `/` | Home (CMS page or fallback view) |
| `/pages/{slug}` | Static site pages |
| `/search` | Site search |
| `/login`, `/register` | Fortify (when enabled) |
| `/account` | User profile (when enabled) |

## License

**MIT** — free for commercial and personal use. See [LICENSE](LICENSE).
