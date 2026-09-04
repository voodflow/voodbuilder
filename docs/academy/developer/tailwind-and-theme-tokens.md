---
title: Tailwind & theme tokens
description: How library blocks use Tailwind CSS and VoodBuilder theme tokens.
---

# Tailwind & theme tokens

VoodBuilder blocks are styled with **Tailwind CSS utility classes**. The editor compiles utilities for the canvas; the public site uses the same class names from saved HTML.

## Prefer utilities in markup

For library **Elements**, put layout and spacing directly in HTML:

```html
<div class="flex flex-col gap-6 py-16 text-vp-fg …">
```

The Style panel adds or removes utilities authors choose in the UI — you do not need per-block CSS for every spacing tweak.

## When stable CSS classes are required

Some patterns are hard to express reliably with utilities alone:

| Pattern | Approach |
|---------|----------|
| Hero full-bleed cover | Stable classes `.voodbuilder-hero-media`, `.voodbuilder-hero-media__img`, `.voodbuilder-hero-media__shade` in shared theme CSS |
| Media frames | `.voodbuilder-media-frame` hooks |
| Absolute fill layers | Shared utilities + optional inline `position/inset` |

Cover heroes **must not** rely only on `h-full w-full object-cover` without the stable hero-media classes — public render can letterbox otherwise.

After adding new block HTML files, ensure their paths are included in the Tailwind **content sources** for the VoodBuilder theme build, then run `npm run build`.

## Theme tokens (`vp-*`)

Use design tokens so light/dark and brand colours stay coherent:

| Token family | Example |
|--------------|---------|
| Background | `bg-vp-bg`, `bg-vp-surface`, `bg-vp-bg-elv` |
| Text | `text-vp-fg`, `text-vp-muted` |
| Brand | `text-vp-brand-1`, `bg-vp-brand-1` |
| Borders | `border-vp-divider` |

::: warning No black borders
Never use bare `border` without a soft token — use `border border-vp-divider` or `ring-1 ring-black/5`.
:::

Avoid hard-coded `dark:` variants when a `vp-*` token exists. Exception: intentional cinematic bands (e.g. always-dark hero) when product design requires it.

## BEM-style hooks

Use `voodbuilder-*` class prefixes for **JS selectors and structural hooks**, not as a replacement for Tailwind layout utilities in new blocks.

## Editor vs public parity

Authors expect WYSIWYG behaviour:

1. Utilities in saved HTML should appear on the public page without extra author steps.
2. Critical layout (hero cover, shade `pointer-events-none`) must work in both contexts.
3. Run a quick check: edit block → save → view without `?edit=1`.

## Content width attributes

First content wrapper inside a section:

```html
<div
  class="voodbuilder-editor-container …"
  data-voodbuilder-role="content"
  data-voodbuilder-content-width="normal"
>
```

Authors cycle full / normal / custom via the toolbar — your markup must keep section full and content wrapper separate.

Next: [Registering blocks](./registering-blocks)
