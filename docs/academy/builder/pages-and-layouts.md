---
title: Pages & layouts
description: Create site pages and choose layout behaviour.
---

# Pages & layouts

**Admin → Site Pages** manages public pages. Each page has a URL, builder mode, and layout that affects chrome and canvas width.

## Builder modes (core)

| Mode | Use when |
|------|----------|
| **Rich editor** | Long-form articles with TipTap / rich blocks |
| **Visual builder** | Marketing layouts, landing pages, full visual control |

Only pages set to **Visual builder** open the page editor (`?edit=1`).

## Common page fields (core)

| Field | Purpose |
|-------|---------|
| **Title / slug** | Public URL (`/pages/{slug}` or home when configured) |
| **Layout** | Home, Landing, Page — affects chrome and default canvas width |
| **Sub-theme** | Optional visual theme override for this page |
| **Hide header / footer** | Skip managed chrome when appropriate |
| **SEO** | Title, description, and related meta |
| **Home takeover** | Mark a page as the site home |

## Page layout types

Layout names may vary slightly by host app, but the ideas are consistent:

| Layout | Typical use |
|--------|-------------|
| **Home** | Site front page, often full-width |
| **Landing** | Marketing pages, hero sections edge-to-edge |
| **Page** | Standard content width with chrome |

The page **layout** works together with **chrome layouts** (site shell). The chrome layout wraps the page body in header and footer; the page layout controls how the inner canvas behaves.

## Opening the editor

Prefer **Open visual editor** from the admin form. Direct `?edit=1` works for authorized users.

Related: [Chrome layouts](./chrome-layouts), [Opening the editor](./opening-the-editor), [Translations](../getting-started/translations)
