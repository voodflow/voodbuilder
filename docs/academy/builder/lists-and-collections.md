---
title: Lists & collections
description: Repeat grids and feeds bound to model collections.
---

# Lists & collections

**List repeat** turns a container into a loop over model records — cards, grids, feeds, team lists, product rows.

::: info Package
List repeat UI, collection queries, and Pro list controls require the **Dynamics** companion (`voodflow/voodbuilder-dynamic-data`) with the appropriate entitlement. Core exposes the repeat markup contract on the public site when configured.
:::

## When to use list repeat

| Pattern | Approach |
|---------|----------|
| Single spotlight (latest article) | **Latest** binding on one element — [Model integrations](./model-integrations) |
| Grid of many items | **List repeat** on a wrapper |
| Mixed static + dynamic | Static section shell + repeat inside the content dropzone |

## Author workflow

1. Ensure a **model integration** exists for your content type.
2. Build a **card template** inside a Container — one example row with `{alias}.item.*` bindings on title, image, link, etc.
3. Select the wrapper → **Dynamic** tab → **List repeat** (or equivalent).
4. Choose the collection / query (e.g. published articles, limit 6, ordered by date).
5. Save and preview the public page.

Each iteration renders the inner template with `{alias}.item` pointing at the current row.

## Binding inside a repeat

Inside a list repeat, the picker prefers **item** context automatically:

- `articles.item.title` — current card’s title
- `articles.item.url` — link to detail page
- `articles.item.cover` — image field

Outside a repeat, the picker prefers **auth** (if the model is a user) or **latest** for spotlight content.

## Layout tips

- Design **one card** first, then enable repeat on the parent flex/grid container.
- Keep equal-height cards with flex utilities on the Block level.
- Use [Visibility conditions](./visibility-conditions) to hide the whole list when empty.

## Empty states

When no records match the query, the repeat renders nothing. Add a static fallback section with a condition, or handle empty collections in your integration scopes.

Related: [Model integrations](./model-integrations), [Publishing & preview](./publishing-and-preview)
