# Content width — product layout contract

How VoodBuilder treats **page / section / content** width in the GrapesJS editor and on the public site.

## Formula (source of truth)

| Layer | Width | Notes |
|-------|--------|--------|
| **Page** | Full | Landing / full chrome: edge-to-edge canvas |
| **Section** | Always full | Owns background colour, image, video, overlays |
| **First content inside section** | Full **or** restricted | Restricted = **Normal** (`80rem`) or **Custom** (layout `content_max_width`) |

Padding, margin, and other styles still apply as usual on section and content.

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

## Markup standard

```html
<section class="voodbuilder-gjs-section …">
  <!-- optional absolute media — NOT a content container -->
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media">…</div>

  <!-- first content wrapper — owns content-width -->
  <div class="voodbuilder-gjs-container …" data-voodbuilder-role="content">
    …
  </div>
</section>
```

- Do **not** put `voodbuilder-gjs-container` on hero-media layers.
- Chrome footer/nav keep their own containers; do **not** force the section→container model onto footer chrome.

## Toolbar icon (`arrow-autofit-width`)

Shows on full-width page contexts, only on:

1. **Section** (1st level) — cycle applies to the **first content child** (section stays full for backgrounds).
2. **That content wrapper** (2nd level) — cycle applies to itself.

Also (optional/minimal): a **bare** `.voodbuilder-gjs-container` that is a direct child of page content **without** a section parent — not inside nav/footer chrome.

Hidden on: text, images, buttons, deeper nesting, chrome nav/footer trees.

Modes cycle: **Full** → **Normal (80rem)** → **Custom** (only if the chrome layout sets a distinct `content_max_width`).

Attribute: `data-voodbuilder-content-width="full|normal|custom"` plus inline max-width for published HTML.

## Not the same as dynamic data

| Feature | Purpose |
|---------|---------|
| **Content width** | Layout measure (full vs boxed) |
| **Make dynamic** (`data-voodbuilder-bind`) | Bind element to CMS/collection fields |
| **Global text tags** (`{brand_name}`, …) | Plain-text site-wide substitutions |

See [DYNAMIC_DATA_USE_CASES.md](./DYNAMIC_DATA_USE_CASES.md) and [BINDINGS.md](./BINDINGS.md) for bindings/tags.

## Authoring checklist

1. Outer node = `section.voodbuilder-gjs-section` (full bleed for bg).
2. First real content child = `.voodbuilder-gjs-container`.
3. Use the toolbar on section or that container — not on inner text/images.
4. Rebuild section catalog after upstream HTML changes: `php artisan voodbuilder:build-sections` then `npm run build`.
