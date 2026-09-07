---
title: Block authoring
description: Markup contracts for selectable, droppable, theme-aware library blocks.
---

# Block authoring

Goal: library blocks that are **easy to select, drop into, and configure** — and that **match the public render**.

This guide covers the **author-facing markup contract**. You do not need to understand the internal save pipeline to build compliant blocks.

## Non-negotiable principles

1. **Editor ≈ public** — layout-critical rules (hero cover, absolute fill) must render the same on canvas and live site.
2. **One responsibility per layer** — media, shade, and editable content are separate nodes.
3. **Drop only where it makes sense** — decorative layers are never drop targets.
4. **Content panel first** — expose image and CTA traits when the section (or content slot) is selected.
5. **Theme tokens** — use `bg-vp-*`, `text-vp-*`, `border-vp-divider` (avoid hard-coded `dark:` and raw black borders).
6. **No nav/footer in page templates** — chrome layouts already provide site shell.

## Section anatomy

Sections stay **full width** for backgrounds. The **first content wrapper** inside owns content width (full / normal / custom).

```html
<section
  class="voodbuilder-editor-section relative overflow-hidden …"
  data-voodbuilder-section-block="vb-acme.hero"
  style="min-height:70vh;"
>
  <!-- Decorative media — NOT a primary dropzone -->
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media" aria-hidden="true">
    <img class="voodbuilder-hero-media__img" src="…" alt="" style="opacity:0.55;" />
    <div class="voodbuilder-hero-media__shade …" data-voodbuilder-role="shade"></div>
  </div>

  <!-- Primary content — droppable -->
  <div
    class="voodbuilder-editor-container relative z-10 …"
    data-voodbuilder-role="content"
    data-voodbuilder-content-width="normal"
    data-voodbuilder-dropzone="content"
  >
    <div data-voodbuilder-dropzone="copy">…headings, paragraphs…</div>
    <div data-voodbuilder-dropzone="actions">…buttons, links…</div>
  </div>
</section>
```

### Roles (`data-voodbuilder-role`)

| Role | Selectable | Droppable | Notes |
|------|------------|-----------|-------|
| `media` | Yes | **No** | img / picture only |
| `shade` | Locked | **No** | Always non-interactive overlay |
| `content` | Yes | **Yes** | Primary dropzone |
| `copy` / `actions` | Yes | **Yes** | Narrow sub-zones for text vs CTAs |

When registering custom component types in JS, map these to readable Layer names (`Hero media`, `Hero content`, `Actions`).

## Dropzone rules

- Authors must drop **Buttons** into `actions` without the control landing outside the section.
- Do **not** mark a full-height hero wrapper as `data-voodbuilder-dropzone="content"` if it steals button drops.
- Shade and media must **never** highlight as drop targets.
- Sections must **not** nest inside sections.

## Content panel traits

For hero / banner blocks, expose traits on the **section** (or content slot):

- Hero image → Asset Manager / `src` update on media `img`
- Optional opacity, object fit, position

Authors should not need to hide shade layers in Layers to reach the image.

## CTA buttons

Prefer annotated button / link types (`data-voodbuilder-cta`) with label traits — not fragile rich-text-only buttons wrapped as mini-sections.

## Dynamic blocks (companions)

Root dynamic blocks (`data-voodbuilder-block`) expose the same **content-width** toolbar as catalog sections. Do **not** put layout-container picker attributes on companion roots — those activate column tools meant for layout containers, not dynamic lists.

Settings for section width / padding belong in the **context toolbar**, not duplicated in the right settings panel for dynamic blocks.

## Shipping checklist

- [ ] Button drops into `actions`; toolbar does not flicker
- [ ] Text drops into `copy`
- [ ] Content tab changes hero image without Layers hunting
- [ ] Public page: cover image is full-bleed, text readable on shade
- [ ] Theme tokens used; no bare `border` without `border-vp-divider`

Next: [Tailwind & theme tokens](./tailwind-and-theme-tokens) · [Registering blocks](./registering-blocks)
