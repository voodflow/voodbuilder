---
title: Content width
description: Full, Normal, and Custom width for section content.
---

# Content width

VoodBuilder separates **full-bleed backgrounds** from **readable content measure**.

## Formula

| Layer | Width | Notes |
|-------|--------|--------|
| **Page** | Full | Landing / full chrome canvas |
| **Section** | Always full | Owns background colour, image, video, overlays |
| **First content inside section** | Full, Normal, or Custom | Normal ≈ `80rem`; Custom = layout `content_max_width` |

```
┌──────────── page (full) ────────────┐
│ ┌────── section (full + bg) ──────┐ │
│ │  [optional hero-media layers]   │ │
│ │  ┌── content (full|normal) ──┐  │ │
│ │  │  text, grids, CTAs…       │  │ │
│ │  └───────────────────────────┘  │ │
│ └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

## Toolbar control

The **content width** icon appears on:

1. A top-level **Section** — cycles width on its first content child (section stays full)
2. That **content wrapper** itself

Cycle: **Full → Normal → Custom** (Custom only if the chrome layout defines a distinct max-width).

Hidden on deep text/images, and on nav/footer chrome trees.

::: tip Short video needed (~15s)
Select section → click content-width icon → Full / Normal / Custom → watch canvas.
:::

::: tip Screenshot needed
Canvas toolbar content-width control with tooltip showing current mode.
:::

## Not the same as…

| Feature | Purpose |
|---------|---------|
| **Content width** | Layout measure |
| **Make dynamic** | Bind to CMS fields (Dynamics companion) |
| **Global text tags** | `{brand_name}`, `{current_year}`, … |

Related admin setting: [Chrome layouts](../admin/chrome-layouts) → content max-width.
