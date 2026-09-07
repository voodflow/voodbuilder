---
title: Interface tour
description: Admin resources and the visual editor at a glance.
---

# Interface tour

VoodBuilder splits work between the **Filament admin** (structure and settings) and the **visual editor** (design).

## Admin panel (core)

| Resource | Purpose |
|----------|---------|
| **Site Pages** | Create URLs, choose builder mode, assign layout and theme |
| **Menus** | Navigation trees for header and footer blocks |
| **Layouts** (Chrome layouts) | Shared header, page content slot, footer |
| **Settings** | Brand, appearance, SEO defaults |
| **Media library** | Reusable images and videos for the Asset Manager |

::: info Dynamics companion
**Model Integrations** (connect Eloquent models to the editor) appear under admin when the **Dynamics** companion is installed and licensed.
:::

## Visual editor layout

When you open a page in the visual builder, you work in three columns:

| Column | Name | Purpose |
|--------|------|---------|
| Left | **Library** | Elements, Components, Templates |
| Center | **Canvas** | Live page preview, device modes, save |
| Right | **Inspector** | Content, Style, Dynamic, Conditions, Layers |

### Library tabs

- **Elements** — layout primitives (Section, Container, Block) and the marketing **section catalog** (Hero, Features, Pricing, …) **(core)**
- **Components** — saved reusable pieces **(Components companion)**
- **Templates** — apply starter layouts; save/export extras **(Templates Pro for authoring)**

### Inspector tabs

- **Content** — text, links, images, block-specific traits **(core)**
- **Style** — Tailwind utilities, fonts, animation **(core)**
- **Dynamic** — Make dynamic bindings and list repeat **(Dynamics companion for full UI)**
- **Conditions** — show/hide rules **(core)**
- **Layers** — tree view for complex sections **(core)**

## Editor modes

| Mode | What you edit |
|------|----------------|
| **Page** | A site page body; chrome may appear as a locked preview |
| **Chrome layout** | Header, content slot, and footer shell |
| **Popup** | Overlay content **(Popups companion)** |

## Canvas toolbar (core)

Typical controls along the top of the canvas:

- Device breakpoints (desktop / tablet / mobile)
- Undo / redo
- Outline / drop-zone helpers
- Light / dark preview
- Save and **View page** (exit edit mode)

Next: [Your first page](./your-first-page)
