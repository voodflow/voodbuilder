---
title: Site pages
description: Create and manage pages in the Filament admin.
---

# Site pages

**Admin → Site Pages** manages public pages (`/pages/{slug}` and the home page when configured).

## Builder modes

| Mode | When to use |
|------|-------------|
| **Rich editor** | Long-form content with Filament TipTap / custom rich blocks |
| **Visual builder (Editor)** | Marketing layouts, landing pages, full visual control |

Only pages set to the visual builder open the page editor (`?edit=1`).

## Common fields

- **Title / slug** — public URL segment
- **Layout** — Home, Landing, Page (affects chrome and canvas width)
- **Sub-theme** — optional visual theme override for this page
- **Hide header / footer** — skip managed chrome when appropriate
- **SEO** — title, description, and related meta (host-dependent)
- **Home takeover** — mark a page as the site home (confirmation when replacing)

## Translations

If locales are enabled in the host app, you can create/delete translation variants and clone pages. Use the row actions on the list/edit screens.

::: tip Screenshot needed
Pages list with Builder column and “Open visual editor” action.
:::

## Opening the editor

Prefer the admin **Open visual editor** action so permissions and URLs stay correct. Direct `?edit=1` works only for authorized users.

Related: [Opening the editor](../builder/opening-the-editor), [Revisions](../builder/revisions).
