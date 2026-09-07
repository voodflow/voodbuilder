# VoodBuilder (`voodflow/voodbuilder`)

Visual **page builder** and public site shell for **Laravel + Filament 5** — themes, pages, navigation, and a GrapesJS editor with a usable **Community (free)** edition.

![VoodBuilder editor overview](art/editor-overview.png)

> **Docs & pricing:** [voodflow.com](https://voodflow.com)  
> **License:** Community is free to use — see [LICENSE](LICENSE). Paid add-ons are separate products on [voodflow.com](https://voodflow.com).

---

## Features

| Capability | What you get |
|------------|----------------|
| **Visual editor** | Drag-and-drop canvas, device previews, undo / redo, revision history, autosave, style inspector |
| **Community core** | Layout · Basic · Media · Single · Site blocks, plus local marketing sections (hero, features, CTA, FAQ, …) |
| **Theme Studio** | Map colours / sub-themes to Site pages and content channels |
| **Page access** | Public, registered-only, profile-based gates, optional password unlock |
| **Menu-driven URLs** | Pages get public routes from Navigation (e.g. `/about` or `/company/about`) |
| **Media** | [`voodflow/vmedia`](https://github.com/voodflow/vmedia) is required by Composer; register its Filament plugin for the admin media UI |
| **Chrome layouts** | Shared header / footer shells per channel, editable in the same builder |

![Style inspector](art/editor-styles.png)

![Layers panel](art/editor-layers.png)

Optional packages (cookie bar, Elements, Dynamic Data, Templates, Components, Popups, content verticals) extend the same editor. Details: **[voodflow.com](https://voodflow.com)**.

---

## Requirements

- PHP **8.4+**
- Laravel **12+** / **13+**
- Filament **5+**
- Node.js + npm (for the host Vite build)
- Vite + Tailwind CSS **v4** (theme CSS compiles in **your** app)
- [`voodflow/vmedia`](https://github.com/voodflow/vmedia) (pulled in by Composer)

Optional: [laravel/fortify](https://laravel.com/docs/fortify) for public login / `/account`.

---

## Installation

```bash
composer require voodflow/voodbuilder -W
php artisan voodbuilder:install
```

`-W` lets Composer settle Guzzle when a fresh Laravel app locked Guzzle 8 while a dependency needs Guzzle 7.

`voodbuilder:install` publishes config, runs migrations, seeds demo data when allowed, patches Vite / `package.json` (Editor, Tailwind, CodeMirror, fonts, …), runs `npm install`, and **`npm run build`** when npm is on PATH.

Then register plugins on your Filament panel. **Only `VoodbuilderPlugin` is required** — add others only if those packages are installed:

```php
->plugins([
    \Voodflow\Voodbuilder\VoodbuilderPlugin::make(),
    \Voodflow\Vmedia\VmediaPlugin::make(), // recommended: media admin UI
    // \Voodflow\Vcookiebar\VcookiebarPlugin::make(),
    // \Voodflow\Vpopups\VpopupsPlugin::make(),
    // \Voodflow\Vforms\VformsPlugin::make(),
])
```

After install you should see **Pages**, **Menus**, **Theme Studio**, **Settings** (and Layouts when enabled) under the **Voodbuilder** nav group.

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

1. **Admin → Voodbuilder → Pages** — create a page and open the visual editor.  
2. **Admin → Voodbuilder → Menus** — link menu items to the page (or to a named route / URL).  
3. Public URLs come from config:

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

Reserved first segments (`admin`, `vmedia`, `voodbuilder`, `login`, …) never become page sections, so package APIs are not shadowed.

### Page access

Per page you can combine:

- **Visibility** — public / registered users / profile-based gates  
- **Password** — unlock form; session TTL configurable (`password_unlock_minutes`)

---

## Theme Studio

**Admin → Voodbuilder → Theme Studio** maps sub-themes (palettes + optional CSS) to areas:

- Site pages  
- Content channels (docs, tutorials, events, …) when those packages register channels  

Light / dark is a global preference on top of the area theme.

---

## Editor

- Block library with layout, media, and marketing sections  
- Style inspector (Tailwind-aware)  
- Device previews (desktop / tablet / mobile)  
- Undo / redo and page revisions  
- Media picker via vmedia  

---

## Documentation

| Where | What |
|-------|------|
| **[voodflow.com](https://voodflow.com)** | Guides, pricing, demos |
| Package `CHANGELOG.md` | Release notes |
| [SECURITY.md](SECURITY.md) | Vulnerability reporting |

---

## Licence & support

- Community core: free to use — see [LICENSE](LICENSE)  
- Paid add-ons: [voodflow.com](https://voodflow.com)  
- Security: [SECURITY.md](SECURITY.md) (not via public issues)
