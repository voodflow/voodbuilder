---
title: Block authoring
description: Markup contracts for selectable, droppable, theme-aware blocks.
---

# Block authoring

Goal: library blocks that are easy to select, drop into, and configure — and that match the public render.

## Non-negotiable principles

1. **Editor ≈ public** — critical layout (cover heroes, absolute fill) should not rely only on fragile JIT utilities. Prefer stable classes in `section-utilities.css` / `theme.css` and/or inline `position/inset` styles.  
2. **One responsibility per layer** — media, shade, and editable content are distinct nodes.  
3. **Drop only where it makes sense** — decorative layers are not droppable.  
4. **Content panel first** — media/CTA traits when the section (or content slot) is selected.  
5. **Theme tokens** — `bg-vp-*`, `text-vp-*`, `border-vp-*` (avoid hard-coded `dark:`).  
6. **No nav/footer** in page starter templates — chrome-shell already provides them.

## Anatomy

```html
<section
  class="voodbuilder-editor-section relative overflow-hidden …"
  data-voodbuilder-section-block="vb-acme.hero"
  style="min-height:70vh;"
>
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media" aria-hidden="true">
    <img class="voodbuilder-hero-media__img" src="…" alt="" style="opacity:0.55;" />
    <div class="voodbuilder-hero-media__shade …" data-voodbuilder-role="shade"></div>
  </div>

  <div
    class="voodbuilder-editor-container relative z-10 …"
    data-voodbuilder-role="content"
    data-voodbuilder-dropzone="content"
  >
    <div data-voodbuilder-dropzone="copy">…</div>
    <div data-voodbuilder-dropzone="actions">…</div>
  </div>
</section>
```

### Roles (`data-voodbuilder-role`)

| Role | Selectable | Droppable | Notes |
|------|------------|-----------|-------|
| `media` | yes | **no** | img / picture only |
| `shade` | locked / no | **no** | `pointer-events: none` |
| `content` | yes | **yes** | Primary dropzone |
| `copy` / `actions` | yes | **yes** | Narrow sub-zones |

Map attributes in custom `DomComponents` types to `droppable`, `highlightable`, and readable Layer `name`s.

## Content width

Section stays **full**; first content wrapper is Full / Normal / Custom. See the [user content width](../user/builder/content-width) guide.

## Drop checklist before shipping

- [ ] Button drops into `actions` without landing outside the section  
- [ ] Full-height hero wrapper is **not** itself `data-voodbuilder-dropzone="content"` if that steals button drops  
- [ ] Text drops into `copy`  
- [ ] Shade/media never highlight as drop targets  
- [ ] Layers names are human-readable  

## CTA buttons

Prefer annotated CTA / button-link types (`data-voodbuilder-cta`) with label traits — not fragile RTE-only buttons wrapped as mini-sections.

::: tip Screenshot needed
Layers panel for a well-authored hero: Hero media, shade, content, copy, actions.
:::
