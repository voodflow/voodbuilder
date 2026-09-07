---
title: Your first page
description: Create and publish a simple visual page end to end.
---

# Your first page

This walkthrough creates a marketing-style page from scratch using **core** features only.

## Prerequisites

- VoodBuilder installed and assets built
- Filament access with permission to edit site pages
- A default **chrome layout** is helpful but optional for the first try

## Step 1 — Create the page

1. Open **Admin → Site → Pages**.
2. Click **Create**.
3. Set **Title** and **Slug** (the public URL segment).
4. Set **Builder** to **Visual builder (Editor)** — not Rich editor.
5. Choose a **Layout** suited to landing content (e.g. Landing / full-width).
6. Save and publish when ready.

## Step 2 — Open the visual editor

Use **Open visual editor** from the page form. Alternatively, visit the public URL with `?edit=1` while logged in with edit permission.

You should see the Library, Canvas, and Inspector.

## Step 3 — Add structure

1. From **Elements → Layout**, drag a **Section** onto the canvas.  
   A **Container** is usually added inside automatically.
2. With the Container selected, use the column layout tool (or set flex direction to horizontal) and add **Blocks** for columns.
3. Drop **Heading**, **Text**, **Image**, or a catalog **Hero** section from the library.

::: tip Prefer catalog sections
Ready-made Hero, Features, and CTA sections already follow the Section → content wrapper pattern. They are faster than building from raw layout elements alone.
:::

## Step 4 — Style and save

1. Select any element → **Style** tab → adjust spacing, colours, typography.  
   Prefer theme tokens (`text-vp-fg`, `bg-vp-surface`) over raw hex values.
2. Click **Save** on the canvas toolbar.
3. Click **View page** or open the URL **without** `?edit=1` to see the public render.

## Step 5 — Optional site chrome

If your site uses a shared header and footer:

1. Create a **Menu** under **Admin → Menus**.
2. Create a **Chrome layout** with navbar, **content slot**, and footer.
3. Assign the menu to the navbar block in the layout editor.

Details: [Chrome layouts](../builder/chrome-layouts) and [Menus](../builder/menus).

## What’s next

- [Pages & layouts](../builder/pages-and-layouts) — layout types and page fields  
- [Layout model](../builder/layout-model) — Section, Container, Block, Div  
- [Publishing & preview](../builder/publishing-and-preview) — go-live checklist
