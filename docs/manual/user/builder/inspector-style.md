---
title: Inspector — Style
description: Style Manager, Tailwind classes, fonts, and animation.
---

# Inspector — Style

The **Style** tab combines classic Style Manager sectors with VoodBuilder helpers.

## What you can adjust

- Layout: display, direction, gap, alignment, sizing, spacing
- Typography and colour (prefer **theme tokens** `text-vp-*`, `bg-vp-*`)
- Borders, shadows, backgrounds
- **Tailwind class chips** — add/remove/paste classes; live CSS compile on canvas
- **Font family** — search the self-hosted Fontsource catalog (~50 families)
- **Animation** sector where enabled

::: tip Screenshot needed
Style tab with font search and Tailwind class chips on a heading.
:::

## Theme tokens vs raw colours

Blocks from the catalog use tokens so light/dark and brand colours stay coherent. Prefer `bg-vp-bg` / `text-vp-brand-1` over hard-coded `bg-white` / `text-gray-900` unless you intentionally want a fixed cinematic band.

## Live CSS

The editor compiles Tailwind-compatible classes for the canvas. After large imports, check the compatibility report if shown.

Related: [Content width](./content-width), developer [Fonts](../../developer/php-sdk/fonts).
