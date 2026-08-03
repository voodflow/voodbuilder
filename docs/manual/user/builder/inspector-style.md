---
title: Inspector — Style
description: Tailwind utility Style panel, classes chips, fonts, and animation.
---

# Inspector — Style

The **Style** tab applies styling as **Tailwind utility classes** (same pattern as the Animation sector). The canvas engine still owns DnD, layers, and save — not invented inline Style Manager paints.

## What you can adjust

- **Dimension** — width, height, max-width, margin, padding (Tailwind scale)
- **Decorations** — background, border width/style/color, rounded, box-shadow (`shadow-*`)
- **Typography** — font family (Fontsource catalog), font-size, weight, align, text color, leading
- **Animation** — Tailwind CSS Animated utilities (unchanged)
- **Classes chips** — add/remove/paste any utility; live CSS compile on canvas
- **Global classes** — optional named CSS rules (empty until you create one; day-to-day styling uses utilities)

Empty sector fields mean **nothing was authored** for that utility group — no default `text-shadow`, `0 solid black` borders, or `undefined` box-shadows.

## Theme tokens vs raw colours

Prefer theme tokens `text-vp-*` / `bg-vp-*` so light/dark and brand colours stay coherent.

## Live CSS

The editor compiles Tailwind-compatible classes for the canvas via page JIT. After large imports, check the compatibility report if shown.

Related: [Content width](./content-width), developer [Fonts](../../developer/php-sdk/fonts).
