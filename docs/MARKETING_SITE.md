# VoodBuilder — Marketing site (local)

Seed a demo marketing site for the Voodflow plugin ecosystem (local development only).

## Seed

```bash
php artisan voodbuilder:seed-marketing-site
# or
php artisan db:seed --class="Voodflow\Voodbuilder\Database\Seeders\VoodflowMarketingSiteSeeder"
```

## Pages

| Slug | URL | Content |
|------|-----|---------|
| `a` | `/pages/a` | Home — plugin overview |
| `a-plugins` | `/pages/a-plugins` | Full plugin grid |
| `a-{plugin}` | `/pages/a-voodflow`, etc. | One page per plugin |
| `a-docs` | `/pages/a-docs` | Links to vdocs `/docs` |
| `a-tutorials` | `/pages/a-tutorials` | Links to vtuts `/tutorials` |

With `WEB_PORT=8006`: `http://localhost:8006/pages/a`

Content lives in `src/Support/MarketingSiteContent.php`. Images use Unsplash placeholders; replace with vmedia assets in production-like demos.

**Do not deploy** these pages to production without review — intended for local demos only.
