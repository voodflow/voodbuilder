# VoodBuilder (`voodflow/voodbuilder`)

<img class="filament-hidden" src="https://raw.githubusercontent.com/voodflow/voodflow-public-docs/main/voodbuilder/images/promo.png" alt="VoodBuilder — Filament Visual Site Platform by VoodFlow" />

**More than a CMS. More than a page builder.** The Filament-native platform to design, run, and grow your public site.

Built for **Laravel developers** and **agencies** shipping real client sites — not demos. VoodBuilder is the visual site layer of the **[VoodFlow](https://voodflow.com)** ecosystem (media, consent, popups, forms, content verticals, and more on the same Filament stack).

![VoodBuilder editor overview](https://raw.githubusercontent.com/voodflow/voodflow-public-docs/main/voodbuilder/images/editor-overview.png)

> **Docs:** [voodflow.com](https://voodflow.com) · **Live demo:** [demo.voodflow.com](https://demo.voodflow.com)

---

## Features

| Capability | What you get |
|------------|----------------|
| **Visual editor** | Drag-and-drop canvas, device previews, undo / redo, revision history, autosave, style inspector |
| **Core blocks** | Layout · Basic · Media · Single · Site blocks, plus local marketing sections (hero, features, CTA, FAQ, …) |
| **Theme Studio** | Map colours / sub-themes to Site pages and content channels; global light / dark preference |
| **Chrome layouts** | Shared header / footer / optional progress strip per channel; Integration tab for reading typography (live preview when companions register samples) |
| **Site search** | Header palette + full results page — ranking, channel filters, snippets with highlights, deep-linkable pagination |
| **SEO** | Per-page meta (title, description, robots, canonical), JSON-LD breadcrumbs / Article where relevant |
| **Page access** | Public, registered-only, profile-based gates, optional password unlock |
| **Menu-driven URLs** | Pages get public routes from Navigation (e.g. `/about` or `/company/about`) |
| **Reserved paths** | Companions declare URL prefixes so site-page catch-alls never shadow docs / tutorials / APIs |
| **Media (included)** | [`voodflow/vmedia`](https://github.com/voodflow/vmedia) — media library & admin UI, pulled in with Community |
| **Cookie consent (included)** | [`voodflow/vcookiebar`](https://github.com/voodflow/vcookiebar) — consent bar & script gating, pulled in with Community |

![Style inspector](https://raw.githubusercontent.com/voodflow/voodflow-public-docs/main/voodbuilder/images/editor-styles.png)

![Layers panel](https://raw.githubusercontent.com/voodflow/voodflow-public-docs/main/voodbuilder/images/editor-layers.png)

Paid companions (Elements, Dynamic Data, Dynamic API, Templates, Components, Popups, content verticals) extend the same editor. See **Editions** below, try the product on **[demo.voodflow.com](https://demo.voodflow.com)**, or read more on **[voodflow.com](https://voodflow.com)**.

---

## Editions

VoodBuilder ships as **Community** (this package) so you can **evaluate the full visual site workflow** with no licence key. Paid **Developer** and **Agency** editions unlock companion plugins via Anystack — same product family, same Filament panel.

### Community

What you get with `voodflow/voodbuilder` alone — enough to judge fit before you buy:

- Visual page editor (GrapesJS), core blocks, Theme Studio, Chrome layouts
- Site pages, menus, SEO, page access gates, site search shell
- History / revisions, autosave, style inspector
- **[`voodflow/vmedia`](https://github.com/voodflow/vmedia)** and **[`voodflow/vcookiebar`](https://github.com/voodflow/vcookiebar)** included (Composer dependencies — register their Filament plugins for the admin UI)

Install from Packagist / Composer — **no licence key** for Community surfaces. Prefer a guided tour? Open **[demo.voodflow.com](https://demo.voodflow.com)**.

### Developer

For solo developers and small teams building dynamic Filament sites. Adds the day-to-day authoring companions:

- **Elements** — remote Elements library and ready-made blocks
- **Dynamic Data** — bind Eloquent models to drive page content
- **Templates** — save, reuse, and share page templates

**Licence:** **1 seat** (one concurrent activation). **Unlimited sites** on that licence.

**Purchase:** [checkout.anystack.sh/voodbuilder-developer](https://checkout.anystack.sh/voodbuilder-developer)

### Agency

For agencies and multi-person teams. Everything in Developer, plus agency tooling:

- **Popups** — build, publish, and track popups in the same editor
- **Components** — reusable components, UIKit import, custom code import
- **Dynamic API** — HTTP, Eloquent, static, and callback data sources on pages

**Licence:** **5 seats** (five concurrent activations). **Unlimited sites** on that licence.

**Purchase:** [checkout.anystack.sh/voodbuilder-agency](https://checkout.anystack.sh/voodbuilder-agency)

| | Community | Developer | Agency |
|--|:---------:|:---------:|:------:|
| Core editor & Theme Studio | ✓ | ✓ | ✓ |
| vmedia + vcookiebar | ✓ | ✓ | ✓ |
| Elements library | | ✓ | ✓ |
| Dynamic Data | | ✓ | ✓ |
| Templates | | ✓ | ✓ |
| Popups | | | ✓ |
| Components | | | ✓ |
| Dynamic API | | | ✓ |
| Seats | — | 1 | 5 |
| Sites | — | Unlimited | Unlimited |

Companion packages still need to be required with Composer (Developer / Agency bundles) and registered on the Filament panel (see Installation). The licence unlocks their capabilities; Community can install some companions for soft-gated UI, but paid surfaces need a Developer or Agency key.

---

## Requirements

- PHP **8.4+**
- Laravel **12+** / **13+**
- Filament **5+**
- Node.js + npm (for the host Vite build)
- Vite + Tailwind CSS **v4** (theme CSS compiles in **your** app)
- [`voodflow/vmedia`](https://github.com/voodflow/vmedia) (pulled in by Composer)
- [`voodflow/vcookiebar`](https://github.com/voodflow/vcookiebar) (pulled in by Composer)

Optional: [laravel/fortify](https://laravel.com/docs/fortify) for public login / `/account`.

---

## Installation

```bash
composer require voodflow/voodbuilder -W
php artisan voodbuilder:install
```

`-W` lets Composer settle Guzzle when a fresh Laravel app locked Guzzle 8 while a dependency needs Guzzle 7.

`voodbuilder:install` publishes config, runs migrations, seeds **settings + empty menus** (no sample pages by default), patches Vite / `package.json` (Editor, Tailwind, CodeMirror, fonts, …), runs `npm install`, and **`npm run build`** when npm is on PATH.

Then register plugins on your Filament panel. **Only `VoodbuilderPlugin` is required** — add others only if those packages are installed:

```php
->plugins([
    \Voodflow\Voodbuilder\VoodbuilderPlugin::make(),
    \Voodflow\Vmedia\VmediaPlugin::make(), // recommended: media admin UI
    \Voodflow\Vcookiebar\VcookiebarPlugin::make(), // recommended: consent / script gating
    // \Voodflow\Vpopups\VpopupsPlugin::make(),
    // \Voodflow\Vforms\VformsPlugin::make(),
])
```

After install you should see **Pages**, **Menus**, **Theme Studio**, **Settings** (and Layouts when enabled) under the **Voodbuilder** nav group.

**First visit to `/`:** with no home page yet, the public site shows a setup card (create a **Layout**, then a **Page**). The default Blade header/footer is hidden on that screen — it is not a Layout record. Opt-in sample pages: `VOODBUILDER_SEED_SAMPLE_PAGES=true`.

### Frontend build

Theme CSS and the editor compile through **your** app’s Vite (`public/build/manifest.json`). The package cannot ship a one-size-fits-all prebuilt bundle.

- Default: `voodbuilder:install` already runs `npm run build`
- Skip compile: `--skip-npm-build` / `--skip-npm`, then later `npm run build` or `npm run dev`
- If the public site shows a yellow “assets not built” notice, run `npm run build` from the app root

### Admin menu & permissions

- **Without Filament Shield:** anyone who can open the Filament panel sees VoodBuilder resources
- **With Filament Shield:** generate and assign resource permissions, or set `VOODBUILDER_AUTHORIZATION=panel` in `.env` to allow all panel users

> Stock Laravel `GET /` in `routes/web.php` overrides the VoodBuilder home route — the install command removes it.  
> Do **not** publish duplicate package migrations.

Flags: `--skip-npm`, `--skip-npm-build`, `--skip-seed`, `--skip-migrate`, `--force`.

---

## Site pages & routing

1. **Admin → Voodbuilder → Layouts** — create a site shell (header/footer), then enable it.  
2. **Admin → Voodbuilder → Pages** — create a home page and open the visual editor.  
3. **Admin → Voodbuilder → Menus** — link menu items to the page (or to a named route / URL).  
4. Public URLs come from config:

| Mode | Example |
|------|---------|
| Prefixed (default) | `/pages/about` |
| Menu paths (`menu_paths`) | `/about` or `/company/about` from the menu tree |

```php
'pages' => [
    'enabled' => true,
    'route_prefix' => 'pages',   // '' + menu_paths → clean URLs from Navigation
    'menu_paths' => false,
],
```

Reserved first segments (`admin`, `vmedia`, `voodbuilder`, `login`, …) never become page sections, so package APIs are not shadowed. Companions can also call `Voodbuilder::reservePathPrefix()` for their public routes.

---

## Theme Studio

**Admin → Voodbuilder → Theme Studio** maps sub-themes (palettes + optional CSS) to areas:

- Site pages  
- Content channels (docs, tutorials, events, search, …) when those packages register channels  

Light / dark is a global preference on top of the area theme.

---

## Chrome layouts

**Admin → Voodbuilder → Layouts** — edit the shared shell (header, optional Progress zone, page content slot, footer) in the same visual editor.

- **Progress zone** — drop Reading Progress between header and page content so it is not nested in the nav  
- **Integration tab** — typography controls (primary / secondary fonts, body + heading scales) with live preview when a companion registers `Voodbuilder::readingPreview()`  
- Companions own reading layouts (`vdocs`, `vtuts`, …); core stays layout-agnostic via `Voodbuilder::reservePathPrefix()`

---

## Site search

Built-in search across content channels that opt in:

- Header search palette (live matches, keyboard nav, recent queries)  
- Results page with ranking, channel filter pills, `<mark>` highlights, and `?page=` pagination  
- Settings → **Search**: results per page, per channel, snippet length  

Companions make content searchable via the PHP SDK — details on [voodflow.com](https://voodflow.com).

---

## SEO & page access

Per page you can combine:

- **Visibility** — public / registered users / profile-based gates  
- **Password** — unlock form; session TTL configurable (`password_unlock_minutes`)  
- **SEO** — meta title, description, robots, canonical on the morph `seo` relation; gated pages default to `noindex` unless overridden  

---

## Editor

- Block library with layout, media, and marketing sections  
- Style inspector (Tailwind-aware)  
- Device previews (desktop / tablet / mobile)  
- Undo / redo and page revisions  
- Media picker via vmedia  
- Utilities such as reading progress and social share (specialized settings)

---

## Documentation

| Where | What |
|-------|------|
| **[demo.voodflow.com](https://demo.voodflow.com)** | Live demo — try VoodBuilder in the browser |
| **[voodflow.com](https://voodflow.com)** | Product site, guides, and ecosystem |
| Package `CHANGELOG.md` | Release notes |
| [SECURITY.md](SECURITY.md) | Vulnerability reporting |

---

## Support

- Demo: [demo.voodflow.com](https://demo.voodflow.com)  
- Docs / product: [voodflow.com](https://voodflow.com)  
- Security: [SECURITY.md](SECURITY.md) (not via public issues)
