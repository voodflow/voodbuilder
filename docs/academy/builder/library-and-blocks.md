---
title: Library & blocks
description: Elements, section catalog, and optional editor plugins.
---

# Library & blocks

The left **Library** column has tabs for **Elements**, **Components**, and **Templates**.

## Elements tab (core)

### Layout primitives

Section, Container, Block, Div — see [Layout model](./layout-model).

### Section catalog (core)

60+ marketing sections grouped roughly as:

Hero · Features · Content · Articles · CTA · Contact · Gallery · Stats · Steps · Team · Testimonials · Pricing · Header · Footer · …

Sections adapt to light/dark via theme tokens (`bg-vp-*`, `text-vp-*`).

### Landing kits (core)

Starter sets (Landing 01–03, etc.) and composed page templates.

### Media blocks (core)

Background image/video, sliders, hero media patterns.

### Site chrome blocks (core)

Navbar, footer variants, and the **chrome content slot** (layouts only — not for page bodies).

### Basic & plugin blocks (core, config-dependent)

Optional toggles in host config may enable:

- Forms
- Style background
- Tabs
- Custom code

## Components tab

Save and reuse selections from your pages. **Requires Components companion** — without it, the tab shows a soft gate.

## Templates tab

- **Apply** templates to the current page — **core**
- Save selection, JSON import/export, multi-select authoring — **Templates Pro companion**

See [Page templates](./page-templates).

## Pins (core)

Pin frequently used blocks in the library for quicker access.

## Dropzones (core)

Catalog heroes expose structural roles:

| Role | Droppable? | Purpose |
|------|------------|---------|
| `media` | No | Background image/video |
| `shade` | No | Overlay on media |
| `content` | Yes | Primary drop target |
| `copy` / `actions` | Yes | Headings vs buttons |

Drop text into **copy** zones and buttons into **actions** — not onto decorative media layers.

Related: [Canvas & inspector](./canvas-and-inspector), [Developer — Block authoring](../developer/block-authoring)
