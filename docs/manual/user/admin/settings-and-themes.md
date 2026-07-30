---
title: Settings & themes
description: Appearance, brand, SEO, and visual themes.
---

# Settings & themes

**Admin → VoodBuilder settings** (exact label may vary) controls site-wide options.

## Appearance

- Light / dark preference for the public site (and editor canvas toggle)
- Brand name, logo, and related identity fields
- SEO defaults and analytics hooks when configured

## Visual themes (sub-themes)

VoodBuilder distinguishes:

| Concept | Meaning |
|---------|---------|
| **Light / dark** | Colour mode |
| **Visual theme (sub-theme)** | Design system for an area (landing, docs, …) — tokens like `bg-vp-bg`, `text-vp-brand-1` |

Assign themes **by area / content channel** so documentation can look different from marketing pages. Pages may override the theme individually.

::: tip Screenshot needed
Settings screen — themes by area / theme map.
:::

## Theme map / studio

Where enabled, a theme map UI helps assign and preview themes across channels. Treat large visual changes as a design pass: rebuild front-end assets after theme CSS changes.

Related developer topics: [Sub-themes](../../developer/php-sdk/sub-themes), [Fonts](../../developer/php-sdk/fonts).
