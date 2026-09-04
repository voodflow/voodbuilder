---
title: Canvas & inspector
description: Toolbar, Style panel, Content traits, and Layers.
---

# Canvas & inspector

## Canvas toolbar (core)

| Control | Purpose |
|---------|---------|
| Device breakpoints | Desktop / tablet / mobile preview |
| Undo / redo | Revert canvas changes before save |
| Outlines / drop zones | Visualize structure while dragging |
| Class hover tip | Inspect Tailwind classes under the pointer |
| Preview | Quick public-style preview |
| Light / dark | Canvas colour mode |
| Zoom | Scale the canvas |
| Toggle panels | Hide library or inspector |
| Save / View page | Persist and exit edit mode |

## Floating toolbar (core)

When a component is selected:

- Select parent · drag handle · move up/down · clone · delete
- **Edit image** (when applicable)
- **Content width** (on sections / content wrappers)
- Context insert — button, text, icon, divider, image, link

## Inspector — Content (core)

Shows traits for the selected block:

- Text, links, button labels
- Image URL + Asset Manager choose/remove
- Hero background opacity, fit, position
- Navbar/footer: menu, logo, newsletter, social
- Form fields, tab labels, code source

Prefer Content-panel image controls over hunting nested `<img>` nodes under shade layers.

### Dynamic tab

**Make dynamic**, clear binding, and list repeat live here. Full UI requires the **Dynamics companion**; without it you may see an upsell. See [Model integrations](./model-integrations).

## Inspector — Style (core)

Applies **Tailwind utility classes**:

- Dimension — width, height, margin, padding
- Decorations — background, border, radius, shadow
- Typography — font family, size, weight, colour
- Animation — Tailwind CSS Animated utilities
- **Classes chips** — add, remove, or paste any utility

Prefer theme tokens (`text-vp-fg`, `bg-vp-surface`) over raw hex colours.

## Layers (core)

Tree view for complex sections. Rename nodes (`Hero media`, `Hero content`, `Actions`) so deep structures stay navigable.

## Context menu (core)

Right-click for insert, clone, delete, and related actions. Managed chrome regions reject page-content drops.

Related: [Media & images](./media-and-images), [Visibility conditions](./visibility-conditions)
