---
title: Understanding the layout
description: Section, Container, Block, and Div — the VoodBuilder layout model.
---

# Understanding the layout

VoodBuilder ships four layout elements to group and lay out content in a fast, predictable way (inspired by modern builders such as Bricks):

- **Section** — structure / divide the page (one topic per section)
- **Container** — constrain content width; hosts columns
- **Block** — flex child at 100% width (e.g. a column)
- **Div** — plain grouping element

::: tip Screenshot needed
Two-column layout: Section → Container → two Blocks with sample content.
:::

If you are new to layout, prefer **Section → Container → Block**. They ship with presets that work out of the box. Under the hood they are div/section nodes with flex-friendly defaults so you can direct, align, and space children easily.

The **Div** is the most basic grouper: minimal presets, grows with its children, style freely.

All layout elements expose display-oriented controls in **Style** (flex/grid/block, etc.). Theme tokens (`bg-vp-*`, `text-vp-*`) keep light/dark consistent.

### Quick comparison

| Element | Typical tag | Width idea | Where to use |
|---------|-------------|------------|--------------|
| **Section** | `section` | Always full (owns background) | Root level |
| **Container** | `div` | Content measure (full / normal / custom) | Inside Section |
| **Block** | `div` | 100% of parent | Inside Container (columns) |
| **Div** | `div` | Content-sized | Anywhere |

---

## Section

Use sections as the outermost building block to separate parts of the page.

- Takes **100%** of available width
- Height follows content (or min-height you set)
- Stacks vertically
- Owns **background** colour, image, video, and overlays

When you add a Section, a **Container** is typically added inside for content. You can remove it for edge-to-edge heroes where media layers sit directly in the section.

::: tip Screenshot needed
Hero section with full-bleed background image and centered content container.
:::

**Content width tip:** the section stays full; the first content wrapper inside it is what you box to Normal/Custom. See [Content width](./content-width).

---

## Container

Place a Container inside a Section. Put Blocks / Divs inside the Container for multi-column or multi-row layouts.

- Acts as the primary **content** wrapper (`data-voodbuilder-role="content"` in catalog sections)
- Participates in the content-width toolbar (Full / Normal / Custom)
- Column picker: select the Container and choose a preset column layout

::: tip Screenshot needed
Container selected with floating column / “Insert layout” picker.
:::

---

## Block

The Block provides the same layout spirit as a column: **100% width** flex child inside a Container (or Section).

Use Blocks for equal columns:

1. Select the Container  
2. Set direction to **horizontal** (or use the layout picker)  
3. Insert three Blocks  

Result: **Section → Container → 3 Blocks**.

::: tip Screenshot needed
Structure panel showing Section > Container > Block, Block, Block.
:::

---

## Div

The Div is the generic grouper. Prefer it when you need a wrapper without Section/Container semantics (nested groups, card shells, flex helpers).

---

## How to insert and nest

- Drag from the **Elements** library onto the canvas or onto a highlighted dropzone
- Use the canvas **+** / context insert for quick text, button, icon, image, link
- Right-click (context menu) for clone, delete, and insert helpers
- Hold modifier keys where the UI indicates insert *after* the selection (host shortcuts)

### Layers panel

Rename nodes in **Layers** so complex heroes stay navigable (`Hero media`, `Hero content`, `Actions`). Avoid hiding shade layers with `display: none` permanently — that can persist to the public page.

::: tip Short video needed (~25s)
Insert Section → pick 2-column layout → drop text in each Block → save.
:::

## Catalog sections

Beyond raw layout elements, the library includes **ready-made sections** (Hero, Features, Pricing, …). They already follow the Section → content wrapper contract. Prefer them for speed; dig into layout elements when you need a custom structure.

Next: [Content width](./content-width) · [Library & blocks](./library-and-blocks)
