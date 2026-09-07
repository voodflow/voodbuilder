---
title: Chrome layouts
description: Shared header, content slot, and footer for your site.
---

# Chrome layouts

A **chrome layout** is the reusable shell around page content:

```
┌─────────────────────────────────────┐
│  Header (logo, menu, CTA)          │
├─────────────────────────────────────┤
│  Page content slot                  │
├─────────────────────────────────────┤
│  Footer                             │
└─────────────────────────────────────┘
```

Managed under **Admin → Layouts** (Chrome layouts). **Core.**

## The content slot

Every chrome layout must contain exactly one **page content slot**. On the public site, that marker is replaced by the current page body (or plugin content).

::: warning
Do not delete the content slot. Without it, pages cannot inject their HTML into the shell.
:::

## How layouts are chosen (core)

When a visitor loads a page, VoodBuilder resolves chrome in this order:

1. Enabled **non-default** layout whose **content channels** include the current page’s channel
2. Peer channel fallback when configured (e.g. docs ↔ tutorials)
3. Enabled layout marked **default** (catch-all)
4. Classic app layout without VoodBuilder chrome

Assign **content channels** on the layout form. Mark at most one layout as **default**.

## Content max-width (core)

Layouts can define a **custom content max-width**. In the page editor, the content-width toolbar can cycle **Full → Normal (80rem) → Custom** when a custom value exists. See [Content width](./content-width).

## Editing visually (core)

Open a chrome layout in the **layout editor mode**:

1. Edit header and footer blocks on the canvas.
2. Keep the content slot in place.
3. Save the layout.

When editing a **page**, header and footer usually appear as a **managed preview** — visible for context but locked. Edit chrome in the layout editor, not on individual pages.

## Do not duplicate chrome in pages

Page templates and starter sections **omit** nav and footer on purpose. The chrome layout already provides them. Adding another navbar inside page HTML causes double headers.

Related: [Menus](./menus), [Opening the editor](./opening-the-editor)
