---
title: Getting started
description: Create your first visual page in VoodBuilder.
---

# Getting started

This walkthrough takes you from an empty admin to a published visual page.

## Prerequisites

- Access to the Filament admin panel
- Permission to edit site pages and open the visual editor
- Host app assets built (`npm run build` or `npm run dev`) so the editor loads

## 1. Create a page

1. Open **Admin → Site → Pages** (labels may vary by host app).
2. Create a new page.
3. Set **Builder** to **Visual builder (Editor)** (not Rich editor).
4. Choose a **layout** suitable for marketing pages (Home or Landing / full-width).
5. Optionally pick a **sub-theme** (visual theme) for the page.
6. Save / publish.

::: tip Screenshot needed
Site page form with Builder = Visual builder and Layout = Landing.
:::

## 2. Open the visual editor

From the page edit screen, use **Open visual editor** (or visit the public URL with `?edit=1` while logged in with permission).

You should see three columns:

| Column | Purpose |
|--------|---------|
| **Left — Library** | Elements (blocks), Components, Templates |
| **Center — Canvas** | The page; device breakpoints, undo/redo, save |
| **Right — Inspector** | Content, Style, Dynamic, Conditions, Layers |

::: tip Screenshot needed
Editor shell with Library, Canvas, and Inspector annotated.
:::

::: tip Short video needed (~30s)
Create page → open editor → drop a Section → save → view public page.
:::

## 3. Build a simple layout

1. From **Elements**, drag a **Section** onto the canvas (a Container is added inside).
2. Select the Container → use the column layout tool (or set Direction to horizontal) → add **Blocks**.
3. Drop text, images, or a catalog section (Hero, Features, CTA, …) into place.
4. Click **Save**, then **View page** / exit edit mode.

Learn the model in depth: [Understanding the layout](./builder/understanding-the-layout) and [Content width](./builder/content-width).

## 4. Wire site chrome (optional)

If your site uses a shared header/footer:

1. Create menus under **Menus**.
2. Create a **Chrome layout** with header, **page content slot**, and footer.
3. Assign channels or mark it as default.

See [Chrome layouts](./admin/chrome-layouts) and [Site chrome in the editor](./builder/site-chrome).

## Next steps

- [Canvas & toolbar](./builder/canvas-and-toolbar)
- [Library & blocks](./builder/library-and-blocks)
- [Inspector — Style](./builder/inspector-style) (fonts, classes, theme tokens)
- [Publishing & preview](./publishing-and-preview)
