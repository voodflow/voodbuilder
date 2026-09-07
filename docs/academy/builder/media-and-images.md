---
title: Media & images
description: Asset Manager, hero images, and in-canvas editing.
---

# Media & images

## Media library (core)

**Admin → Media library** stores reusable images and videos (Spatie Media Library). The editor **Asset Manager** reads from the same library.

| Action | Where |
|--------|-------|
| Upload bulk assets | Admin media library |
| Choose image in editor | Content tab → **Choose** / Asset Manager |
| Upload from editor | Asset Manager or Jodit upload endpoint |

## Hero and background images (core)

When a **section** or media role is selected:

1. Open **Content** tab.
2. Use **Hero image** (or equivalent trait) → Asset Manager.
3. Adjust opacity, object fit, or position when traits are available.

Prefer Content-panel controls over selecting hidden `<img>` nodes under shade layers in Layers.

## In-canvas image editing (core)

Select an image → floating toolbar **Edit image** opens the crop/filter editor (Jodit). On save, uploads update `src` on the element.

## Static vs dynamic images

| Type | Behaviour | Package |
|------|-----------|---------|
| Static image | URL or library pick; editable in Content | **Core** |
| Dynamic image | Bound to a model field; no manual URL edit | **Dynamics** |

When an image has a dynamic binding, manual URL controls are hidden.

## Tips

- Use descriptive alt text on content images (accessibility and SEO).
- For full-bleed hero covers, use catalog hero sections — they include stable cover styling.
- Rebuild assets after theme changes: `npm run build`.

Related: [Canvas & inspector](./canvas-and-inspector), [Model integrations](./model-integrations)
