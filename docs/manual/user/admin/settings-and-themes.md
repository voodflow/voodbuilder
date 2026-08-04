---
title: Settings & Theme Studio
description: Site settings plus first-class Theme Studio for visual themes and channel assignments.
---

# Settings & Theme Studio

## Settings

**Admin → VoodBuilder → Settings** covers site-wide options that are not visual theme orchestration:

- Branding (title, favicon)
- Appearance: light / dark mode, sticky nav, account, language switcher
- SEO / GEO / Analytics defaults

## Theme Studio

**Admin → VoodBuilder → Theme Studio** is the first-class surface for visual themes:

- Browse bundled themes and your clones
- Clone, import, export, and edit theme colors
- Assign themes to site areas (Landing, Docs, Tutorials, Blog, …) on the theme map

VoodBuilder distinguishes:

| Concept | Meaning |
|---------|---------|
| **Light / dark** | Colour mode (Settings → Appearance) |
| **Visual theme (sub-theme)** | Design system for an area — tokens like `bg-vp-bg`, `text-vp-brand-1` (Theme Studio) |

Assign themes **by area / content channel** so documentation can look different from marketing pages. Pages may override the theme individually when enabled.

::: tip Screenshot needed
Theme Studio — workspace cards + theme map.
:::

Related developer topics: [Sub-themes](../../developer/php-sdk/sub-themes), [Fonts](../../developer/php-sdk/fonts).
