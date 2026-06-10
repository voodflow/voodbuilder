# voodflow/vpress

**Free & Open Source (MIT)** — VitePress-style public frontend for Laravel with a **Filament 5** admin panel.

Companion plugins [voodflow/vtuts](https://github.com/voodflow/vtuts) and [voodflow/vdocs](https://github.com/voodflow/vdocs) are **paid, source-available** packages (not Open Source).

Vpress is **not a full CMS** and **requires Filament 5** for site pages, navigation, and settings. It is a **lightweight site shell**: a handful of managed pages, navigation, SEO defaults, theme (light/dark), optional auth, notifications, and layouts tuned for **documentation** (`vdocs`) and **tutorials** (`vtuts`). Think “VitePress chrome + Filament admin for site settings”, not WordPress.

## What it does

| Area | What you get |
|------|----------------|
| **Public theme** | VitePress-like nav, doc sidebar, outline scroll-spy, reading progress, mobile drawer, dark/light mode |
| **Sub-themes** | Visual variants (documentation, blog, news, custom) — site default + per-page override |
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
4. Seed default navigation, demo pages (blog + news sub-themes), and cookie policy page
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

- **Settings** — branding, SEO, light/dark default, **site sub-theme**, feature toggles, primary locale
- **Pages** — home and static pages (RichEditor + blocks, **per-page sub-theme**)
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
   - **Sub-theme** — inherit the site default, or pick **Documentation**, **Blog**, **News**, or a custom theme.
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
- Blog (demo) → `https://yoursite.test/pages/blog`
- News (demo) → `https://yoursite.test/pages/news`
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

`vpress:install` seeds a **Main navigation** menu (Home, Tutorials when vtuts is installed, **Blog**, **News**) and a **Footer** menu (Privacy Policy, Cookie Policy). Blog and News are demo pages that showcase the built-in sub-themes.

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

## Sub-themes

Sub-themes are **visual variants** of the public shell (layout, typography, colours). They are separate from **light/dark mode**, which is still controlled in **Settings → Header & appearance**.

### Built-in sub-themes

| ID | Label | Best for |
|----|-------|----------|
| `default` | Documentation | Marketing home, docs-style pages (VitePress layout) |
| `blog` | Blog | Long-form articles, Ghost-inspired centered reading column |
| `news` | News | Editorial / magazine headlines and wider columns |

After `vpress:install`, open the main menu and visit:

| Menu item | URL | Sub-theme | What you see |
|-----------|-----|-----------|--------------|
| Home | `/` | Documentation | Marketing home with hero + features |
| Blog | `/pages/blog` | Blog | Section index — 5 posts, left nav + right sidebar |
| News | `/pages/news` | News | News desk — lead story + grid, editorial sidebars |

Each section ships **five navigable articles** seeded under `/pages/blog-*` and `/pages/news-*`. Article pages reuse the section sidebars, breadcrumb, and sibling navigation.

**Blog articles (demo)**

| Slug | URL |
|------|-----|
| `blog-welcome` | `/pages/blog-welcome` |
| `blog-shipping-shell` | `/pages/blog-shipping-shell` |
| `blog-sub-themes` | `/pages/blog-sub-themes` |
| `blog-content-blocks` | `/pages/blog-content-blocks` |
| `blog-pairing-vtuts` | `/pages/blog-pairing-vtuts` |

**News articles (demo)**

| Slug | URL |
|------|-----|
| `news-morning-briefing` | `/pages/news-morning-briefing` |
| `news-sub-themes-release` | `/pages/news-sub-themes-release` |
| `news-editorial-workflow` | `/pages/news-editorial-workflow` |
| `news-community-notes` | `/pages/news-community-notes` |
| `news-roadmap` | `/pages/news-roadmap` |

Re-seed demo content anytime:

```bash
php artisan migrate
php artisan db:seed --class="Voodflow\Vpress\Database\Seeders\VpressSeeder"
npm run build
```

### Site default vs per-page

| Where | Field | Behaviour |
|-------|-------|-----------|
| **Settings** | Sub-theme | Default for the whole public site |
| **Pages → Publish** | Sub-theme | Override for that page only (`Site default` inherits from Settings) |

Use per-page sub-themes to run different **sections** under one menu — e.g. documentation on `/`, a blog area on `/pages/blog`, and a news desk on `/pages/news`.

### Section pages (blog / news)

Group related pages with the **Section** field in **Pages**:

| Field | Purpose |
|-------|---------|
| **Section** | `blog` or `news` — groups pages for sidebar navigation |
| **Section home** | Index page that lists all articles in the section |
| **Excerpt** | Card summary on section indexes and for SEO |

Section home pages render a multi-column layout (sidebar left, main feed, sidebar right). Article pages keep the same sidebars plus a breadcrumb back to the section home. The main menu highlights **Blog** or **News** while you browse any page in that section.

> **Demo vs real CMS:** the seeded blog/news sections are **Site Pages grouped by `section`** — fine for marketing demos, not a full post archive. For a real blog or news product, use a dedicated package and register a **content channel** (below).

### Mobile navigation

The mobile drawer is **theme-agnostic** (`resources/css/mobile-nav.css`): same slide-in panel, colours, and footer toolbar on every sub-theme. It slides in from the right with logo, main links, optional extras, then search / language / theme / account in a sticky footer.

### Integrating external blog, news, or other content

Vpress is a **site shell**, not a post CMS. Third-party or custom packages integrate via **content channels**:

| Piece | What you register |
|-------|-------------------|
| **Routes** | Your package owns `/blog`, `/blog/{slug}`, etc. |
| **Menu** | Filament → Navigation → **App route** `blog.index` with active pattern `blog.*` |
| **Sub-theme** | Channel `sub_theme` → `blog` so layouts/CSS match the section |
| **Search** | Optional `search` callback or model with `vpressSearch()` |
| **Admin** | Your Filament resources — not Site Pages |

**Config** (`config/vpress.php`):

```php
'content_channels' => [
    'blog' => [
        'label' => 'Blog',
        'routes' => ['blog.*'],
        'sub_theme' => 'blog',
        'search' => \App\Models\BlogPost::class,
    ],
],
```

**Or in `AppServiceProvider`:**

```php
use Voodflow\Vpress\Vpress;

Vpress::contentChannel('blog', [
    'label' => 'Blog',
    'routes' => ['blog.*'],
    'sub_theme' => 'blog',
    'search' => fn (string $term, int $limit) => BlogPost::query()
        ->where('title', 'like', "%{$term}%")
        ->limit($limit)
        ->get()
        ->map(fn ($post) => [
            'title' => $post->title,
            'url' => route('blog.show', $post),
            'excerpt' => $post->excerpt,
        ]),
]);
```

**Search model contract** (optional):

```php
public static function vpressSearch(string $term, int $limit): \Illuminate\Support\Collection
{
    // return items with title, url, optional excerpt
}
```

Your package typically:

1. Ships models + migrations + public controllers.
2. Points views at `vpress::layouts.app` or a sub-theme layout (`article`, `section_index`).
3. Registers the channel so menu highlighting and sub-theme follow the active route.

Same pattern as **vtuts** / **vdocs**: companion package + shared vpress chrome, not duplicated Site Pages.

### Custom sub-themes

Scaffold a theme in your application:

```bash
php artisan vpress:make-subtheme magazine --label="Magazine"
```

This creates `resources/vpress/themes/magazine/theme.css`, Blade layouts under `resources/views/vpress/themes/magazine/`, registers the theme in `config/vpress.php`, and tries to add the CSS entry to `vite.config.js`. Then run `npm run build`.

Register themes programmatically:

```php
use Voodflow\Vpress\Vpress;

Vpress::subTheme('magazine', [
    'label' => 'Magazine',
    'description' => 'Custom editorial layout.',
    'layouts' => [
        'home' => 'vpress.themes.magazine.layouts.home',
        'page' => 'vpress.themes.magazine.layouts.page',
    ],
    'css' => 'resources/vpress/themes/magazine/theme.css',
]);
```

Built-in themes are declared in `config/vpress.php` under `sub_themes`. Each theme may override `home` and `page` layouts and ship extra CSS scoped with `html[data-vpress-sub-theme="…"]`.

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

- `config/vpress.php` — layouts, sub-theme registry, feature flags, Vite entry paths (committed)
- **Database** (`VpressSettings`) — logo, titles, light/dark default, site sub-theme, toggles (edited in Filament)

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
