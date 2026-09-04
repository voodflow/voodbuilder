---
title: Site settings
description: Brand, appearance, and SEO defaults in the admin.
---

# Site settings

**Admin → VoodBuilder → Settings** holds site-wide options that apply across pages and chrome.

## Branding (core)

- **Site title** — used in SEO and global text tags (`{site_name}`)
- **Brand name** — resolves `{brand_name}` in text and default footer copy
- **Favicon** — browser tab icon

## Appearance (core)

- **Light / dark mode** — default colour mode for the public site
- **Sticky navigation** — keep the header visible while scrolling
- **Account / language switcher** — show or hide chrome controls when your host app supports them

::: info Visual themes
**Visual themes** (design tokens like `bg-vp-bg`, `text-vp-brand-1`) are managed separately from light/dark mode. Theme assignment by site area may be available in **Theme Studio** when enabled in your project — documentation for Theme Studio will be added when the workflow is finalised.
:::

## SEO defaults (core)

Set default meta title patterns, descriptions, and related SEO fields. Individual pages can override SEO on their edit form.

## Media library (core)

Reusable uploads live in **Admin → Media library**. Images chosen in the editor Asset Manager draw from the same library.

## Relationship to pages

| Setting | Effect |
|---------|--------|
| Brand / site name | Global text tags and default chrome labels |
| Appearance mode | Public light/dark unless a page forces otherwise |
| SEO defaults | Fallback when a page has no custom meta |

Next: [Translations](./translations) · [Builder overview](../builder/)
