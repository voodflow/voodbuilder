---
title: Global text tags
description: Site-wide placeholders in text and rich content.
---

# Global text tags

Insert plain-text **placeholders** that resolve on the public site. **Core** — no companion required.

## Available tags

| Tag | Resolves to |
|-----|-------------|
| `{current_year}` | Calendar year |
| `{brand_name}` | Brand from settings |
| `{site_name}` | SEO site name or site title |
| `{site_url}` | Application root URL |
| `{logged_username}` | Authenticated user’s name (empty for guests) |

Example in a text block:

```text
© {current_year} {brand_name}. Hello {logged_username}!
```

- Logged-in visitor named Paolo → `© 2026 Acme. Hello Paolo!`
- Guest → `© 2026 Acme. Hello !` — use [Visibility conditions](./visibility-conditions) to hide the greeting for guests

Default footer copyright often uses `© {current_year} {brand_name}`.

## Where tags work (core)

| Surface | Resolved on public site |
|---------|-------------------------|
| Page text and rich text | Yes |
| Site chrome (nav/footer layouts) | Yes |
| Editor canvas | Tags stay visible so authors see tokens |

## Editor behaviour

- Type `{tags}` directly or use the tag picker where available.
- Copyright fields that show a resolved year are re-tagged on save so the year stays dynamic.

## Not the same as model bindings

| Need | Use |
|------|-----|
| Brand, year, site URL, quick username | Global text tags **(core)** |
| Article title, product fields, images from DB | Make dynamic **(Dynamics)** |

Related: [Model integrations](./model-integrations)
