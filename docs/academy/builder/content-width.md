---
title: Content width
description: Full-bleed sections vs boxed content inside sections.
---

# Content width

Sections stay **full width** for backgrounds and media. The **first content wrapper** inside a section can be full, normal (80rem), or custom. **Core.**

## The rule

| Layer | Width |
|-------|--------|
| Page | Full |
| Section | Always full (backgrounds, hero media) |
| First content child inside section | Full, Normal, or Custom |

```
┌──────────── page (full) ────────────┐
│ ┌──── section (full + bg) ──────┐ │
│ │  [optional hero media layers]   │ │
│ │  ┌── content (normal) ───────┐ │ │
│ │  │  headings, grids, CTAs    │ │ │
│ │  └───────────────────────────┘ │ │
│ └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

## Toolbar control (core)

Select a **Section** or its **content wrapper** → use the **content width** icon on the floating toolbar.

Modes cycle:

1. **Full** — edge-to-edge content
2. **Normal** — ~80rem max width
3. **Custom** — uses the chrome layout’s custom max-width when defined

Hero backgrounds remain full bleed; only the inner content box changes.

## Not the same as dynamic data

| Feature | Purpose | Package |
|---------|---------|---------|
| Content width | Layout measure | **Core** |
| Make dynamic | Bind to model fields | **Dynamics** |
| Global text tags | `{brand_name}`, etc. | **Core** |

Related: [Layout model](./layout-model), [Chrome layouts](./chrome-layouts)
