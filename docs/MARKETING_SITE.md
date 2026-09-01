# VoodBuilder — Marketing site (local)

Seed a demo marketing site for the Voodflow plugin ecosystem (local development only).

## Seed

```bash
php artisan voodbuilder:seed-marketing-site
# or
php artisan db:seed --class="Voodflow\Voodbuilder\Database\Seeders\VoodflowMarketingSiteSeeder"
```

Safe to re-run: pages and menus are upserted (menu items are replaced each run). Does **not** run `migrate:fresh`.

## Pages

| Slug | URL | Content |
|------|-----|---------|
| `a` | `/pages/a` | Home — plugin overview |
| `a-plugins` | `/pages/a-plugins` | Full plugin grid |
| `a-{plugin}` | `/pages/a-voodflow`, etc. | One page per plugin |
| `a-docs` | `/pages/a-docs` | Links to vdocs `/docs` |
| `a-tutorials` | `/pages/a-tutorials` | Links to vtuts `/tutorials` |

With `WEB_PORT=8006`: `http://localhost:8006/pages/a`

Home is slug **`a`** at **`/pages/a`** (not the site root `/`). All pages use locale **`en`**.

Content lives in `src/Support/MarketingSiteContent.php`. Menu structure lives in `src/Support/MarketingSiteMenus.php`. Images use Unsplash placeholders; replace with vmedia assets in production-like demos.

## Menus (locale `en`)

The seeder creates or updates VoodBuilder navigation menus:

### `main` — header navigation

| Item | Type | Target |
|------|------|--------|
| Home | Site page | `a` |
| Plugins | Dropdown group | — |
| → All plugins | Site page | `a-plugins` |
| → {each plugin} | Site page | `a-voodflow`, `a-voodbuilder`, … |
| Docs | Site page | `a-docs` |
| Tutorials | Site page | `a-tutorials` |

### `footer` — inline footer links

Home, Docs, Tutorials (same page slugs as above).

### Footer columns

| Slug | Content |
|------|---------|
| `footer_col_1` | Home, All plugins, Docs, Tutorials |
| `footer_col_2` | One link per plugin page |
| `footer_col_3` | Empty (placeholder column) |
| `footer_col_4` | Privacy / Cookie policy pages when they exist |

**Do not deploy** these pages to production without review — intended for local demos only.
