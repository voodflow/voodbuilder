# VoodBuilder (`voodflow/voodbuilder`)

Visual **page builder** and public site shell for **Laravel + Filament 5** — themes, pages, navigation, and a GrapesJS editor that ships with a usable **Community (free)** edition.

![VoodBuilder editor overview](art/editor-overview.png)

> **Docs:** product guides and release notes live on **[voodflow.com](https://voodflow.com)** (not in this repository).  
> **License:** Community is **free to use** (source-available — see [LICENSE](LICENSE)). Pro / Agency unlock paid companions via [Anystack](https://anystack.sh) / your Voodflow account. This is **not** an OSI open-source licence.

---

## Why teams pick VoodBuilder

| Capability | What you get |
|------------|----------------|
| **Visual editor** | Drag-and-drop canvas, device previews, **undo / redo**, **revision history**, autosave, style inspector (Tailwind-aware) |
| **Community free core** | Layout · Basic · Media · Single · Site + ~10 local marketing sections (hero, features, CTA, FAQ, …) including Animates & Tabs |
| **Theme Studio** | Map colours / sub-themes to Site pages, Docs, Events, and other content channels — live preview |
| **Page access** | Public · registered-only · profile / paywall-style gates · **password unlock** (session) |
| **Menu-driven URLs** | Pages get public routes from Navigation (flat `/about` or nested `/company/about`) — no hand-written `routes/web.php` per page |
| **Media (included)** | **`voodflow/vmedia`** — vault, galleries, Editor picker (alt / caption / credits) |
| **Cookie consent (included)** | **`voodflow/vcookiebar`** — MIT consent bar that can actually block scripts until accepted |
| **Chrome layouts** | Shared header / footer shells per channel, editable in the same builder |
| **Soft-gate companions** | Components, Templates, Dynamic Data, Elements library, Popups — upsell in-editor when the companion / licence is missing |

Pages are authored **only in VoodBuilder** (no Filament RichEditor content path). What you design in the canvas is what the public site renders.

![Style inspector](art/editor-styles.png)

![Layers panel](art/editor-layers.png)

---

## Editions (high level)

| Edition | Includes |
|---------|----------|
| **Community (0 €)** | Core builder + local section pack + Theme Studio + page access + **vmedia** + **vcookiebar** |
| **Professional** | + remote **Elements** library + Dynamic Data + Templates (with companions) |
| **Agency** | + **Components** library / global classes |
| **VoodPopups** (add-on) | Popup designer — **requires** `voodbuilder ^0.1` (same editor) |

Full pricing and entitlement setup: see **[voodflow.com](https://voodflow.com)** / your Anystack products.

---

## Bundled free dependencies

These ship with VoodBuilder (Composer `require`):

| Package | Role |
|---------|------|
| [`voodflow/vmedia`](https://github.com/voodflow/vmedia) | Media vault & galleries · Editor “Select image” browser · MIT + trademark NOTICE |
| [`voodflow/vcookiebar`](https://github.com/voodflow/vcookiebar) | Cookie / consent bar · can gate third-party scripts · MIT |

Install them once with VoodBuilder; register their Filament plugins alongside `VoodbuilderPlugin` when you want the admin UIs.

---

## Paid / optional companions

| Package | Role |
|---------|------|
| `voodflow/voodbuilder-elements` | Remote section library (`api.voodflow.com`) |
| `voodflow/voodbuilder-dynamic-data` | Bindings, Model Integrations, List repeat |
| `voodflow/voodbuilder-templates` | Save / import / export page templates |
| `voodflow/voodbuilder-components` | Reusable components + global CSS classes |
| `voodflow/vpopups` | Popups (triggers, targeting, analytics) — requires VoodBuilder |
| `voodflow/vforms` | Forms product (separate SKU; optional editor blocks) |

Verticals such as **vevents / vpartners / vsponsors / vexhibitors** and docs/tutorials (**vdocs / vtuts**) integrate as content channels when installed — they are not part of the builder Community SKU.

---

## Requirements

- PHP **8.4+**
- Laravel **12+** / **13+**
- Filament **5+**
- Vite + Tailwind CSS **v4** (theme CSS compiles in **your** app)
- [ralphjsmit/laravel-seo](https://github.com/ralphjsmit/laravel-seo)
- [spatie/laravel-settings](https://github.com/spatie/laravel-settings)
- [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary) (via vmedia)

Optional: [laravel/fortify](https://github.com/laravel/fortify) for public login / `/account`.

---

## Installation

```bash
composer require voodflow/voodbuilder
php artisan voodbuilder:install --with-npm-build
```

Typical first install also pulls **vmedia** and **vcookiebar**. Then register Filament plugins:

```php
use Voodflow\Voodbuilder\VoodbuilderPlugin;
use Voodflow\Vmedia\VmediaPlugin;
use Voodflow\Vcookiebar\VcookiebarPlugin;

$panel->plugins([
    VoodbuilderPlugin::make(),
    VmediaPlugin::make(),
    VcookiebarPlugin::make(),
]);
```

`voodbuilder:install` publishes config, runs migrations, seeds demo navigation/pages when allowed, patches Vite / `package.json`, and can compile assets (`--with-npm-build`).

> Stock Laravel `GET /` in `routes/web.php` overrides the VoodBuilder home route — the install command removes it.  
> Do **not** publish duplicate package migrations.

Flags: `--skip-npm`, `--skip-seed`, `--skip-migrate`, `--force`.

---

## Site pages & routing

1. **Admin → Site → Pages** — create a page, design it in the visual editor (`?edit=1`).
2. **Admin → Site → Navigation** — link menu items to the page (or to a named route / URL).
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

Published content keeps rendering even if a Pro licence later lapses (authoring soft-gates; front stays up).

---

## Theme Studio

**Admin → Themes** maps sub-themes (palettes + optional CSS) to areas:

- Site pages  
- Content channels (docs, tutorials, events, …) when those packages register channels  

Light / dark is a global preference on top of the area theme. Popup editor can **preview as** any mapped area (runtime popups still inherit the host page theme).

---

## Editor highlights

- **Library** — Layout, Basic, Media, Single, Site (+ Animates / Tabs in Community; more with Pro / Elements)
- **Undo / redo** — canvas history  
- **Revisions** — restore earlier saves when History is enabled  
- **Media browser** — galleries from vmedia (not the stock GrapesJS asset manager)  
- **Responsive** — desktop / tablet / mobile frames  
- **Soft-gates** — companion tabs (Components, Templates, Dynamic, Popups) explain how to unlock

---

## Documentation

| Where | What |
|-------|------|
| **[voodflow.com](https://voodflow.com)** | User docs, pricing, demos |
| Package `CHANGELOG.md` | Release notes for this version |
| `art/` | README screenshots |
| [SECURITY.md](SECURITY.md) | How to report vulnerabilities |

Product documentation is **not** stored in this repository.

---

## Support & licence

- **Community:** free forever for the core builder scope above (see [LICENSE](LICENSE))  
- **Pro / Agency / Popups:** commercial licence via Voodflow / Anystack  
- **Security:** see [SECURITY.md](SECURITY.md) — do not open public issues for vulnerabilities  
- **Trademark:** “VoodBuilder” / “Voodflow” — see NOTICE files in companion MIT packages  

Questions → [voodflow.com](https://voodflow.com)
