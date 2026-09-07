# VoodBuilder — Marketing site (local)

Seed a demo **marketing landing site** for the Voodflow plugin ecosystem (local development only). This is a sales-oriented site with hero sections, product landings, and chrome layout — not documentation pages.

## Seed

```bash
php artisan voodbuilder:seed-marketing-site
# or
php artisan db:seed --class="Voodflow\Voodbuilder\Database\Seeders\VoodflowMarketingSiteSeeder"
```

Safe to re-run: layout, pages, and menus are upserted (menu items are replaced each run). Stale `a-*` pages from older seeders are removed. Does **not** run `migrate:fresh`.

## Chrome layout

The seeder creates a **Chrome layout** (`voodflow-marketing`) assigned to the `pages` channel:

- Dark marketing header with `site_nav_simple` (main menu)
- Content slot for page body
- Four-column footer with `site_footer_columns_simple`

All marketing pages reference this layout via `chrome_layout_id` and use the `site` sub-theme.

## Pages

| Slug | URL | Content |
|------|-----|---------|
| `a` | `/pages/a` | Home — suite overview, product grid, premium & commercial teasers |
| `a-voodflow` | `/pages/a-voodflow` | Voodflow workflow automation landing |
| `a-voodbuilder` | `/pages/a-voodbuilder` | VoodBuilder visual site builder landing |
| `a-events-suite` | `/pages/a-events-suite` | Events & Exhibitors package (vevents, vexhibitors, vpartners, vsponsors) |
| `a-vdocs` | `/pages/a-vdocs` | vdocs documentation plugin landing |
| `a-vtuts` | `/pages/a-vtuts` | vtuts tutorials plugin landing |

With `WEB_PORT=8006`: `http://localhost:8006/pages/a`

Home is slug **`a`** at **`/pages/a`** (not the site root `/`). All pages use locale **`en`**.

### Not separate plugin pages

These are **not** seeded as standalone landings:

- **Voodflow AI / Flow Weaver** — presented as premium extensions on the home and Voodflow pages
- **White-label licensing** and **OEM / multi-tenant** — presented as commercial purchase options (footer + Voodflow page), not as product features

Content lives in `src/Support/MarketingSiteContent.php`. Chrome layout in `src/Support/MarketingSiteLayout.php`. Menu structure in `src/Support/MarketingSiteMenus.php`. Images use Unsplash placeholders; replace with vmedia assets in production-like demos.

## Menus (locale `en`)

The seeder creates or updates VoodBuilder navigation menus:

### `main` — header navigation

| Item | Type | Target |
|------|------|--------|
| Home | Site page | `a` |
| Products | Dropdown group | — |
| → Voodflow | Site page | `a-voodflow` |
| → VoodBuilder | Site page | `a-voodbuilder` |
| → Events & Exhibitors | Site page | `a-events-suite` |
| → vdocs | Site page | `a-vdocs` |
| → vtuts | Site page | `a-vtuts` |
| Contact | External URL | `https://voodflow.com` |

### `footer` — inline footer links

Home, Voodflow, Contact.

### Footer columns

| Slug | Content |
|------|---------|
| `footer_col_1` | Home, Voodflow, VoodBuilder |
| `footer_col_2` | All product landing pages |
| `footer_col_3` | White-label licensing, OEM / multi-tenant, Contact |
| `footer_col_4` | Privacy / Cookie policy pages when they exist |

**Do not deploy** these pages to production without review — intended for local demos only.
