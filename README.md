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
6. Remove Laravel’s default `Route::get('/')` welcome route so vpress can serve the homepage
7. Patch `vite.config.js` with the correct theme CSS path when possible
8. If `voodflow/vtuts` is installed — patch `config/vtuts.php` to use `vpress::layouts.*`

> **Important:** A stock Laravel app defines `GET /` in `routes/web.php`, which overrides the vpress `home` route and shows the default welcome page without the vpress theme. `vpress:install` removes that route automatically.

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

## Site pages

Site pages are managed in **Admin → Site → Pages**. They are stored in the `vpress_pages` table and served by vpress public routes — no manual `routes/web.php` entry is required for each page.

### Creating a page

1. Open **Pages → Create**.
2. Fill in **Title** — the slug is generated automatically from the title on first save (you can edit it before publishing).
3. Write content in the **RichEditor** — use headings, lists, links, and **custom blocks** (Hero, Features grid, Partner banner, and blocks registered by other packages such as `latest_vtuts`).
4. In the **Publish** sidebar:
   - **Published** — must be enabled for the page to appear on the public site.
   - **Published at** — optional schedule; leave empty or set a past date to publish immediately.
   - **Layout** — `Standard page` or `Home (full width)` (see below).
   - **Home page** — mark exactly one page as the site homepage (`/`).

5. Save. Use the **View** action (eye icon) in the Publish panel to open the public URL in a new tab when the page is published.

### Routing and public URLs

| Page kind | Public URL | Laravel route | Notes |
|-----------|------------|---------------|-------|
| **Home page** | `/` | `home` | Set via **Home page** toggle; slug is fixed to `home` and cannot be changed |
| **Static page** | `/pages/{slug}` | `vpress.pages.show` | Default prefix is `pages` (configurable) |

Examples:

- Home → `https://yoursite.test/`
- About → `https://yoursite.test/pages/about`
- Privacy Policy → `https://yoursite.test/pages/privacy-policy`

If no published home page exists (or it has no content), `GET /` falls back to `vpress::pages.welcome` with SEO defaults from **Settings**.

Configure the pages route in `config/vpress.php`:

```php
'pages' => [
    'enabled' => true,           // set false to disable /pages/{slug}
    'route_prefix' => 'pages',   // e.g. 'p' → /p/about
],
```

Disable the home route separately:

```php
'home' => [
    'route_enabled' => true,
],
```

### Layouts per page

| Layout (Filament) | Blade layout | Use for |
|-------------------|--------------|---------|
| **Home (full width)** | `vpress::layouts.home` | Homepage hero, feature grids, marketing sections |
| **Standard page** | `vpress::layouts.page` | Legal pages, about, simple content |

The home page always uses the **Home** layout. Other pages default to **Standard page**. Both render through `vpress::pages.site-page`, which outputs the RichEditor HTML and custom blocks.

### SEO

Each page uses [ralphjsmit/laravel-seo](https://github.com/ralphjsmit/laravel-seo) via the `HasSEO` trait. Title comes from the page title; the meta description is derived from the rendered content excerpt. Global defaults (site title, default description, social image) are set in **Settings**.

### Drafts and the home page

- Unpublished pages are not reachable on the public site (`published()` scope).
- The home page cannot be deleted from the list; you can replace its content or unpublish it.
- Only one page can have **Home page** enabled at a time.

## Navigation menus

Menus are managed in **Admin → Site → Navigation**. Each menu record has a **placement** (`slug`) that tells the theme where to render it, and an ordered list of **items**.

### Menu placements

Create one menu per placement (the `slug` field is unique):

| Placement (`slug`) | Where it appears |
|--------------------|------------------|
| `main` | Center of the header navbar (desktop and mobile drawer) |
| `header_extra` | Right side of the header, before language switcher / theme toggle / account / search |
| `footer` | Footer link row above the copyright line |

`vpress:install` seeds a **Main navigation** menu (Home + Tutorials when vtuts is installed) and a **Footer** menu (Privacy Policy, Cookie Policy).

> Use **header_extra** for secondary links such as Shop, Blog, or Pricing that should sit on the right side of the navbar.

### Menu items

Each item has:

| Field | Description |
|-------|-------------|
| **Label** | Text shown in the nav |
| **Type** | How the link target is resolved (see below) |
| **Link** | Page slug, route name, or URL depending on type |
| **Active route pattern** | Wildcard pattern for highlight state (auto-filled for pages and app routes) |
| **Open in new tab** | Adds `target="_blank"` |

Drag items in the repeater to reorder them (`sort_order`).

### Item types

#### 1. Site page

Links to a vpress page by slug.

- Select the page from a searchable dropdown (draft pages are listed with a “Draft” suffix).
- The URL is resolved at render time from the published page (`/` for home, `/pages/{slug}` otherwise).
- **Active route pattern** is set automatically: `home` for the home page, or left empty for static pages (active state matches `vpress.pages.show` + slug).

#### 2. App route

Links to a named Laravel route registered in your application (e.g. `vtuts.index`, `vdocs.index`, `home`).

- Choose from a **searchable select** of public **GET** routes (`MenuRouteCatalog`).
- Admin, Livewire, Filament, and other internal routes are excluded via `config('vpress.menus.route_exclude_patterns')`.
- **Active route pattern** is filled automatically when you pick a route, e.g.:
  - `vtuts.index` → `vtuts.*` (highlights on all tutorial pages)
  - `vtuts.series.lesson` → `vtuts.series.*`
  - `home` → `home`

You can add or remove exclude patterns in `config/vpress.php`:

```php
'menus' => [
    'route_exclude_patterns' => [
        'filament.*',
        'livewire.*',
        // …
    ],
],
```

#### 3. External URL

Links to an absolute URL (`https://…`) or a site path (`/docs/`, `/shop`).

- Enter the URL manually in the **Link** field.
- **Active route pattern** is shown only for this type — use it when you need a custom highlight rule for external targets (optional; usually leave empty).

### Active (current) link highlighting

The theme compares the current request against each item’s `route_match` (or page slug for site pages) and applies an active CSS class. This keeps parent items highlighted on child routes — e.g. **Tutorials** stays active on `/tutorials/my-post` when `route_match` is `vtuts.*`.

### Example menus

**Main navigation — Tutorials (vtuts installed)**

| Label | Type | Link | route_match |
|-------|------|------|-------------|
| Home | App route | `home` | `home` |
| Tutorials | App route | `vtuts.index` | `vtuts.*` |

**Footer — legal pages**

| Label | Type | Link |
|-------|------|------|
| Privacy Policy | Site page | `privacy-policy` |
| Cookie Policy | Site page | `cookie-policy` |

**Header extras — external shop**

| Label | Type | Link | Open in new tab |
|-------|------|------|-----------------|
| Shop | External URL | `https://shop.example.com` | Yes |

### Cache

Menu items are cached for one hour per placement (`vpress.menu.{slug}`). The cache is cleared when menus are saved in Filament.

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

Vpress does not ship pre-built CSS. `php artisan vpress:install` tries to add the theme entry to your `vite.config.js` automatically. The path depends on how the package is installed:

| Install method | Theme CSS path |
|----------------|----------------|
| Composer (GitHub / Packagist) | `vendor/voodflow/vpress/resources/css/theme.css` |
| Path repo / monorepo | `packages/voodflow/vpress/resources/css/theme.css` |

`config/vpress.php` resolves this at runtime — you do not need to edit it manually.

Add Tailwind and fonts, then build:

```bash
npm install -D @fontsource-variable/inter @fontsource/jetbrains-mono tailwindcss @tailwindcss/vite
npm run build
```

Your `vite.config.js` should look like this (path may differ):

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
                'vendor/voodflow/vpress/resources/css/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
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
