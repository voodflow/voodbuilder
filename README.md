# voodflow/voodbuilder

**Free & Open Source (MIT)** — VitePress-style public frontend for Laravel with a **Filament 5** admin panel.

Companion plugins [voodflow/vtuts](https://github.com/voodflow/vtuts) and [voodflow/vdocs](https://github.com/voodflow/vdocs) are **paid, source-available** packages (not Open Source).

Voodbuilder is **not a full CMS** and **requires Filament 5** for site pages, navigation, and settings. It is a **lightweight site shell**: a handful of managed pages, navigation, SEO defaults, theme (light/dark), optional auth, notifications, and layouts tuned for **documentation** (`vdocs`) and **tutorials** (`vtuts`). Think “VitePress chrome + Filament admin for site settings”, not WordPress.

## Documentation

| Guide | Topics |
|-------|--------|
| [**docs/VISUAL_THEMES.md**](docs/VISUAL_THEMES.md) | Mental model, admin “theme bindings” & presets, common site setups, create custom themes |
| [**docs/GRAPESJS.md**](docs/GRAPESJS.md) | Page builder, Tailblocks, custom blocks, dynamic blocks |
| [**docs/BUILD.md**](docs/BUILD.md) | `npm run build`, Vite entries, when to recompile |

**Quick mental model:** *Visual theme* = layout + colours per area (marketing vs docs). *Light/dark* = global toggle. *Preset* = shortcut that fills the theme dropdowns — not a separate system.

## What it does

| Area | What you get |
|------|----------------|
| **Public theme** | VitePress-like nav, doc sidebar, outline scroll-spy, reading progress, mobile drawer, dark/light mode |
| **Sub-themes** | Visual variants (documentation, blog, news, events, custom) — site default, per-page override, or per content channel |
| **Site pages** | Home + static pages built with Filament RichEditor and custom blocks (hero, features grid, latest vtuts, …) |
| **Navigation** | Main, header-extra, and footer menus — route names, URLs, or site pages |
| **Settings (DB)** | Brand name, site title, logo, favicon, social image, theme default, locale, toggles (search, theme, language, bell) |
| **SEO** | Integrates [ralphjsmit/laravel-seo](https://github.com/ralphjsmit/laravel-seo); global defaults from Settings |
| **Auth (Fortify)** | Optional public `/login` and `/register` using **Laravel Fortify** views styled like the theme |
| **Account** | `/account` profile page (avatar, name) when enabled |
| **Notifications** | Bell in the nav for logged-in users (DB `notifications` table); e.g. new comments on your content |
| **Search** | `/search` across vtuts, vdocs, and site pages when routes exist |
| **Cookie consent** | Public banner only (admin configures policy in Filament; banner is **not** shown in the panel) |

Voodbuilder does **not** ship blog posts, e-commerce, or arbitrary content types — pair it with **voodflow/vtuts**, **voodflow/vdocs**, or **Relaticle Ink** for that.

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
            "url": "https://github.com/voodflow/voodbuilder.git"
        }
    ],
    "require": {
        "voodflow/voodbuilder": "^0.0.2"
    }
}
```

```bash
composer update voodflow/voodbuilder
php artisan voodbuilder:install --with-npm-build
```

That is usually enough for a fresh Laravel app with Vite. The install command publishes configs, runs migrations, seeds demo data, patches `package.json` and `vite.config.js`, runs `npm install`, and (with `--with-npm-build`) compiles assets.

### What `voodbuilder:install` does automatically

| Step | Action |
|------|--------|
| Publish | Spatie Settings, SEO, voodbuilder config |
| Database | `migrate` + default navigation/pages seed |
| `routes/web.php` | Removes Laravel’s default `GET /` welcome route |
| `package.json` | Adds Tailwind, fonts, GrapesJS npm packages |
| `package.json` scripts | Sets `build` to run `voodbuilder:sync-theme-imports` before Vite when safe |
| `vite.config.js` | Adds theme CSS, GrapesJS editor JS/CSS, Tailblocks utilities |
| npm | Runs `npm install` when `npm` is on PATH |
| Cookie consent | Disables Filament auto-discovery for the public banner package |
| vtuts (optional) | Patches `config/vtuts.php` layouts when the package is installed |

**CLI flags**

| Flag | Use when |
|------|----------|
| `--with-npm-build` | Compile theme + GrapesJS in the same step (recommended on first install) |
| `--skip-npm` | You manage Node dependencies yourself (CI, monorepo tooling) |
| `--skip-seed` | No demo menus/pages |
| `--skip-migrate` | Publish only; run `php artisan migrate` later |
| `--force` | Re-publish configs and re-patch frontend files |

### Manual steps (host app only)

These cannot be automated safely — do them once in **your** Laravel project:

1. **Filament plugin** — register in your panel provider:

```php
use Voodflow\Voodbuilder\VoodbuilderPlugin;

$panel->plugins([
    VoodbuilderPlugin::make(),
]);
```

2. **`vite.config.js` — Tailwind plugin** — if your app does not use Tailwind v4 yet, add the plugin (voodbuilder only patches the `input` array):

```js
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({ /* … */ }),
        tailwindcss(),
    ],
});
```

3. **Custom `resources/js/app.js`** — if you ship your own dark-mode toggle, defer to voodbuilder when the public layout is active (`window.__vpressTheme`). See [docs/BUILD.md](docs/BUILD.md).

> **Important:** A stock Laravel app defines `GET /` in `routes/web.php`, which overrides the voodbuilder `home` route. `voodbuilder:install` removes that route automatically.

> Voodbuilder migrations load from the package automatically. Do not publish duplicate migration files.

**Admin → Site**

- **Settings** — branding, SEO, light/dark default, **site sub-theme**, feature toggles, primary locale
- **Pages** — home and static pages (RichEditor + blocks, **per-page sub-theme**)
- **Navigation** — menus linked to routes or pages

## Site pages

Site pages are managed in **Admin → Site → Pages**. They are stored in the `voodbuilder_pages` table and served by voodbuilder public routes — no manual `routes/web.php` entry is required for each page.

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
| **Static page** | `/pages/{slug}` | `voodbuilder.pages.show` | Default prefix is `pages` (configurable) |

Examples:

- Home → `https://yoursite.test/`
- About → `https://yoursite.test/pages/about`
- Blog (demo) → `https://yoursite.test/pages/blog`
- News (demo) → `https://yoursite.test/pages/news`
- Privacy Policy → `https://yoursite.test/pages/privacy-policy`

If no published home page exists (or it has no content), `GET /` falls back to `voodbuilder::pages.welcome` with SEO defaults from **Settings**.

Configure the pages route in `config/voodbuilder.php`:

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
| **Home (full width)** | `voodbuilder::layouts.home` | Homepage hero, feature grids, marketing sections |
| **Standard page** | `voodbuilder::layouts.page` | Legal pages, about, simple content |

The home page always uses the **Home** layout. Other pages default to **Standard page**. Both render through `voodbuilder::pages.site-page`, which outputs the RichEditor HTML and custom blocks.

### SEO

Each page uses [ralphjsmit/laravel-seo](https://github.com/ralphjsmit/laravel-seo) via the `HasSEO` trait. Title comes from the page title; the meta description is derived from the rendered content excerpt. Global defaults (site title, default description, social image) are set in **Settings**.

### Drafts and the home page

- Unpublished pages are not reachable on the public site (`published()` scope).
- The home page cannot be deleted from the list; you can replace its content or unpublish it.
- Only one page can have **Home page** enabled at a time.

## Navigation menus

Menus are managed in **Admin → Site → Navigation**. Each menu record has a **placement** (`slug`) that tells the theme where to render it, and an ordered list of **items**.

### Nesting (sub-menus)

Voodbuilder supports **2 levels maximum**:

- **Level 1**: top-level navigation items
- **Level 2**: sub-items shown in a dropdown (desktop) / collapsible list (mobile)

The admin UI uses a **drag & drop tree** to reorder items and create sub-menus. If an item is nested deeper than level 2 (e.g. legacy data), it is **flattened automatically** back to level 2 on save / reload so the public UI always stays consistent.

### Menu placements

Create one menu per placement (the `slug` field is unique):

| Placement (`slug`) | Where it appears |
|--------------------|------------------|
| `main` | Center of the header navbar (desktop and mobile drawer) |
| `header_extra` | Right side of the header, before language switcher / theme toggle / account / search |
| `footer` | Footer link row above the copyright line |

`voodbuilder:install` seeds a **Main navigation** menu (Home, Tutorials when vtuts is installed, **Blog**, **News**) and a **Footer** menu (Privacy Policy, Cookie Policy). Blog and News are demo pages that showcase the built-in sub-themes.

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

Drag items in the tree to reorder them (`sort_order`) or drop them onto another item to create a sub-menu.

#### Dropdown group (label-only item)

To create a menu section title / container **without its own link**, set the item type to **Dropdown group**.

- Dropdown groups are useful for containers like **Docs** where the clickable links live in the sub-items.
- A dropdown group can have sub-items, but it does not resolve to a URL on its own.

### Item types

#### 1. Site page

Links to a voodbuilder page by slug.

- Select the page from a searchable dropdown (draft pages are listed with a “Draft” suffix).
- The URL is resolved at render time from the published page (`/` for home, `/pages/{slug}` otherwise).
- **Active route pattern** is set automatically: `home` for the home page, or left empty for static pages (active state matches `voodbuilder.pages.show` + slug).

#### 2. App route

Links to a named Laravel route registered in your application (e.g. `vtuts.index`, `vdocs.index`, `home`).

- Choose from a **searchable select** of public **GET** routes (`MenuRouteCatalog`).
- Admin, Livewire, Filament, and other internal routes are excluded via `config('voodbuilder.menus.route_exclude_patterns')`.
- **Active route pattern** is filled automatically when you pick a route, e.g.:
  - `vtuts.index` → `vtuts.*` (highlights on all tutorial pages)
  - `vtuts.series.lesson` → `vtuts.series.*`
  - `home` → `home`

You can add or remove exclude patterns in `config/voodbuilder.php`:

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

Menu items are cached for one hour per placement (`voodbuilder.menu.{slug}`). The cache is cleared when menus are saved in Filament.

## Sub-themes

Sub-themes are **visual variants** of the public shell (layout, typography, colours). They are separate from **light/dark mode**, which is still controlled in **Settings → Appearance**.

> **Admin UI:** Settings → Theme → **Visual themes by area** (which layout each part of the site uses) and **Quick setups** (presets). See [docs/VISUAL_THEMES.md](docs/VISUAL_THEMES.md) for common site patterns and a simpler explanation.

### Common site setups

| Goal | Start with | Marketing pages | Docs / tutorials |
|------|------------|-----------------|------------------|
| Docs/tutorials only | Preset *Documentation only* | Documentation | Documentation |
| GrapesJS landing only | Showcase + GrapesJS home page | Showcase | — |
| Landing + docs/tutorials | Preset *Cosmolab public site* | Showcase | Documentation |
| GrapesJS home + blog/news | Showcase home + Blog/News channel | Showcase | Blog or News for articles |

Global settings (logo, menus, SEO, dark/light, analytics) are unchanged. See [docs/GRAPESJS.md](docs/GRAPESJS.md) for the page builder.

### Built-in sub-themes

| ID | Label | Best for |
|----|-------|----------|
| `default` | Documentation | Marketing home, docs-style pages (VitePress layout) |
| `blog` | Blog | Long-form articles, Ghost-inspired centered reading column |
| `news` | News | Editorial / magazine headlines and wider columns |
| `events` | Showcase | Trade show / marketing layout — dark header, landing canvas, card grids |

After `voodbuilder:install`, open the main menu and visit:

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
php artisan db:seed --class="Voodflow\Voodbuilder\Database\Seeders\VoodbuilderSeeder"
npm run build
```

### Site default vs per-page vs per-channel

| Where (Filament) | Field | Behaviour |
|------------------|-------|-----------|
| **Settings → Theme → Marketing pages** | Sub-theme | Default for Site Pages (home, `/pages/*`) |
| **Settings → Theme →** *(channel row)* | Sub-theme | Override for route packages (docs, tutorials, blog, events, …) |
| **Pages → Publish** | Sub-theme | Override for that Site Page only |

Use per-page overrides for individual landings. Use channel rows when a whole package area (e.g. `vtuts.*`) should use a different layout than marketing pages.

### Create a custom theme

```bash
php artisan voodbuilder:make-subtheme polito --label="Politecnico di Torino"
# edit resources/voodbuilder/themes/polito/theme.css and layouts/
npm run build
```

Full guide: [docs/VISUAL_THEMES.md](docs/VISUAL_THEMES.md).

### Sub-themes: registry vs files on disk

A **sub-theme** is a named visual skin. It sets `data-voodbuilder-sub-theme="…"` on `<html>` and may ship extra CSS and layout Blade overrides. It is **not** light/dark mode (that is separate).

**Only registered sub-themes appear in admin** (Settings, Pages, Site sections). Registration happens at boot from:

1. **`config/voodbuilder.php` → `sub_themes`** — bundled themes shipped with voodbuilder (`default`, `blog`, `news`, `events`, …)
2. **`php artisan voodbuilder:make-subtheme`** — scaffolds app themes in the same convention and registers them in `config/voodbuilder.php`
3. **`Voodbuilder::subTheme()`** in a ServiceProvider — optional programmatic registration (typically for app-only themes)

**Plugins do not ship themes.** Route packages (vevents, vexhibitors, vtuts, vdocs) register **content channels** only; their default visual theme comes from `config/voodbuilder.php` → `content_channel_defaults`.

See `Voodflow\Voodbuilder\Support\ThemeConvention` for paths and layout namespaces.

| Sub-theme ID | Origin | Views | CSS |
|--------------|--------|-------|-----|
| `default` | `config/voodbuilder.php` | Base `voodbuilder::layouts.*` (no extra folder) | `resources/css/theme.css` |
| `blog`, `news`, `events` | `config/voodbuilder.php` | `voodbuilder::themes.{id}.*` → package `resources/views/themes/{id}/` | package `resources/themes/{id}/theme.css` |
| *(custom)* | `voodbuilder:make-subtheme` or `Voodbuilder::subTheme()` | `voodbuilder.themes.{id}.*` → `resources/views/voodbuilder/themes/{id}/` in the app | `resources/voodbuilder/themes/{id}/theme.css` (auto `@import` into the voodbuilder bundle) |

**vtuts** and **vdocs** use the **doc layout** (`voodbuilder::layouts.doc`). Their channels default to the `default` sub-theme via `content_channel_defaults`; override in **Settings → Theme** (channel row) or register a dedicated skin with `voodbuilder:make-subtheme`.

After adding or changing sub-themes, run `npm run build` so Vite picks up new CSS `@import`s.

### Content channels

A **content channel** connects **route name patterns** to optional **search** and a **default sub-theme**. Channels are how route-based areas (not Site Pages) join the voodbuilder shell.

**A channel only appears in Settings → Theme if something registers it** — usually the package `ServiceProvider`:

```php
// vevents
Voodbuilder::contentChannel('events', new EventsContentChannel);

// vexhibitors, vtuts, vdocs — same pattern
```

voodbuilder itself registers `pages` → `voodbuilder.pages.*` (no default sub-theme; individual Site Pages carry their own).

Alternatively, register from config:

```php
// config/voodbuilder.php
'content_channels' => [
    'blog' => [
        'label' => 'Blog',
        'routes' => ['blog.*'],
        'search' => \App\Models\BlogPost::class,
    ],
],

'content_channel_defaults' => [
    'blog' => 'blog',
],
```

| Piece | Who defines it |
|-------|----------------|
| **Routes** | Your package / `routes/*.php` |
| **Channel + default sub-theme** | `config/voodbuilder.php` → `content_channel_defaults` |
| **Override sub-theme** | Settings → Theme (channel row, DB) |
| **Menu link + active state** | Admin → **Navigation** → App route + `route_match` (e.g. `vevents.*`) |
| **Search** | Channel `search` callback or model `vpressSearch()` |

**Search model contract** (optional):

```php
public static function vpressSearch(string $term, int $limit): \Illuminate\Support\Collection
{
    // return items with title, url, optional excerpt
}
```

Packages typically:

1. Ship models, migrations, and public controllers.
2. Point views at `voodbuilder::layouts.app`, `voodbuilder::layouts.doc`, or a sub-theme layout.
3. Register a content channel so menu highlighting, search, and sub-theme resolution follow the active route.

Same pattern as **vtuts** / **vdocs** / **vevents** / **vexhibitors**: companion package + shared voodbuilder chrome, not duplicated Site Pages.

### How the active sub-theme is chosen

On each public request, `SubThemeResolver::forCurrentRoute()` runs from `voodbuilder::layouts.app`:

```
Current route
    │
    ├─ Matches a content channel? (e.g. vevents.*)
    │       ├─ Admin override in Site sections? → use that sub-theme
    │       └─ Else `content_channel_defaults` in config (e.g. events) → use that
    │
    ├─ Site Page route with page.sub_theme set? → use page override
    │
    └─ Else → Settings → default sub-theme (usually default / Documentation)
```

Invalid or unknown sub-theme IDs fall back to `default`.

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

### Custom sub-themes

Scaffold a theme in your application:

```bash
php artisan voodbuilder:make-subtheme magazine --label="Magazine"
```

This creates `resources/voodbuilder/themes/magazine/theme.css`, Blade layouts under `resources/views/voodbuilder/themes/magazine/`, registers the theme in `config/voodbuilder.php`, and appends an `@import` to the voodbuilder theme bundle. Then run `npm run build`.

Register themes programmatically:

```php
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::subTheme('magazine', [
    'label' => 'Magazine',
    'description' => 'Custom editorial layout.',
    'layouts' => [
        'home' => 'voodbuilder.themes.magazine.layouts.home',
        'page' => 'voodbuilder.themes.magazine.layouts.page',
    ],
    'css' => 'resources/voodbuilder/themes/magazine/theme.css',
]);
```

Bundled **blog**, **news**, and **events** themes live under `packages/voodflow/voodbuilder/resources/themes/` and `resources/views/themes/`. App-specific themes from the CLI use the same structure under `resources/voodbuilder/themes/` and `resources/views/voodbuilder/themes/` in your Laravel app.

Each sub-theme may override `home` and `page` layouts and ship extra CSS scoped with `html[data-voodbuilder-sub-theme="…"]`.

## How it works

### Layouts

| Layout | Use |
|--------|-----|
| `voodbuilder::layouts.app` | Base shell: nav, footer, Vite assets, cookie banner |
| `voodbuilder::layouts.home` | Home page (full-width, no doc sidebar) |
| `voodbuilder::layouts.doc` | Doc/tutorial: fixed gray left sidebar, outline, progress bar |
| `voodbuilder::layouts.page` | Simple content page |

Other Voodflow packages point their config at these layouts (e.g. `vtuts.doc_layout` → `voodbuilder::layouts.doc`).

### Login & registration (Fortify)

When Fortify is installed and routes are registered, voodbuilder serves themed `/login` and `/register` blades. Users created on the public site receive the **registered** role when Shield/vtuts integration is present. Subscriber-only content is enforced by **vtuts** visibility + SubKit, not by voodbuilder alone.

### Notifications

Enable in **Settings** (`show_notification_bell`). Requires Laravel’s `notifications` table (`voodbuilder:install` creates it). The bell Livewire component shows unread Filament/database notifications — useful for moderators when someone comments on a tutorial.

### Settings vs config file

- `config/voodbuilder.php` — layouts, built-in sub-theme registry (`default`, `blog`, `news`), feature flags, Vite entry paths (committed)
- **Database** (`VoodbuilderSettings`) — logo, titles, light/dark default, site sub-theme, **per-channel sub-theme overrides**, toggles (edited in Filament)

`ApplyVoodbuilderSiteConfig` middleware applies DB settings on each web request (title, favicon, locale hints).

### Search

`/search?q=…` queries published **vtuts**, **vdocs** pages, and **site pages** when those packages/routes exist. Disable via settings if not needed.

### With voodflow/vtuts

```bash
php artisan voodbuilder:install
php artisan vtuts:install
```

`voodbuilder:install` sets in `config/vtuts.php`:

- `layout` → `voodbuilder::layouts.page`
- `doc_layout` → `voodbuilder::layouts.doc`

Tutorial listing and doc pages use the voodbuilder shell; vtuts-specific CSS (`vtuts.css`) loads for comments, TOC, materials.

Nav seeder adds a **Tutorials** link when `vtuts.index` exists.

## Vite & CSS

Voodbuilder does not ship pre-built CSS. `php artisan voodbuilder:install --with-npm-build` patches `package.json` and `vite.config.js`, installs npm packages, and can compile assets in one step.

The theme CSS path depends on how the package is installed:

| Install method | Theme CSS path |
|----------------|----------------|
| Composer (GitHub / Packagist) | `vendor/voodflow/voodbuilder/resources/css/theme.css` |
| Path repo / monorepo | `packages/voodflow/voodbuilder/resources/css/theme.css` |

`config/voodbuilder.php` resolves paths at runtime — you do not edit it for Vite entries.

If you skipped the build during install:

```bash
npm run build    # or: npm run dev
```

**Tailwind plugin** — voodbuilder patches the `input` array only. Your `vite.config.js` must still include `@tailwindcss/vite` (see Installation → manual steps).

Example `input` entries after install (paths may differ):

```js
input: [
    'resources/js/app.js',
    'vendor/voodflow/voodbuilder/resources/css/theme.css',
    'vendor/voodflow/voodbuilder/resources/js/grapesjs/editor.js',
    'vendor/voodflow/voodbuilder/resources/css/grapesjs/editor.css',
    'vendor/voodflow/voodbuilder/resources/css/grapesjs/tailblocks-utilities.css',
],
```

Full build guide: [docs/BUILD.md](docs/BUILD.md).

## Custom RichEditor blocks

```php
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::richContentBlock('Dynamic', YourBlock::class);
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
