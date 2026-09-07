---
title: Layout model
description: Section, Container, Block, and Div — the VoodBuilder layout hierarchy.
---

# Layout model

VoodBuilder provides four layout elements to structure pages predictably. **Core.**

| Element | Role | Typical use |
|---------|------|-------------|
| **Section** | Outermost divider; full width; owns background | Page sections (hero, features, footer band) |
| **Container** | Content measure; hosts columns | Inside every section |
| **Block** | Flex child at 100% width | Columns inside a container |
| **Div** | Plain grouping wrapper | Cards, nested groups, helpers |

## Recommended pattern

For most layouts:

```
Section → Container → Block(s) → content
```

Catalog sections (Hero, Features, …) already follow this contract — prefer them for speed.

## Section (core)

- Always **full width**
- Owns background colour, image, video, overlays
- Height follows content (or min-height you set)
- Adding a Section usually inserts a Container inside

Edge-to-edge heroes may place media layers directly in the section with a content wrapper on top.

## Container (core)

- Primary **content wrapper** inside a section
- Participates in the [content-width](./content-width) toolbar
- Column picker: select Container → choose a preset column layout

## Block (core)

- **100% width** flex child — think “column”
- Example: Section → Container (horizontal) → 3 Blocks

## Div (core)

- Generic grouper without section/container semantics
- Use for nested groups, card shells, or flex helpers

## Catalog sections (core)

The library includes 60+ ready-made sections (Hero, Features, Pricing, Testimonials, …). They use theme tokens for light/dark coherence. Drop them instead of rebuilding common patterns from scratch.

## Insert and nest (core)

- Drag from **Elements** onto highlighted dropzones
- Canvas **+** / context menu for quick inserts
- **Layers** panel to select deep nodes — rename them (`Hero content`, `Actions`) on complex heroes

::: warning
Do not permanently hide shade layers with `display: none` in Layers — that can persist to the public page.
:::

Next: [Content width](./content-width) · [Library & blocks](./library-and-blocks)
