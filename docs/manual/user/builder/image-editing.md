---
title: Image editing
description: In-canvas crop, filters, and focus with the Jodit image editor.
---

# Image editing

When enabled, selecting an image (or background-image section) offers **Edit image** on the canvas toolbar. The editor opens **Jodit Image Editor** for crop, filters, finetune, **Focus** (tilt-shift), text, and resize.

Toggle via config / env (`editor.image_editor` / `VOODBUILDER_EDITOR_IMAGE_EDITOR`; legacy key `grapesjs.image_editor`).

## Modal actions

| Button | Behaviour |
|--------|-----------|
| **Cancel** | Close without saving. |
| **Save as copy** | Upload a **new** vault file (linked to the original when the image came from vmedia). |
| **Save** | **Replace** the existing vault file when the image has a vmedia UUID; otherwise same as upload. |

The top Jodit bar keeps **undo / redo / zoom** only — save actions are in the modal footer to avoid duplicate buttons.

On first in-place save, vmedia keeps a single on-disk backup under `.originals/` next to the file (not a second library row).

## Focus tool

Open the **Focus** tab, then **click on the preview** to move the sharp centre (radial or linear). Adjust **Intensity** and **Radius** with the sliders.

## Requirements

- Real raster images (JPEG/PNG/WebP). SVG placeholders cannot be edited.
- Images with dynamic bindings are read-only in the editor.
- vmedia must be enabled for vault **Save** / **Save as copy**; upload URL comes from `EditorGate`.

::: tip Short video needed (~20s)
Select image → Edit image → Focus click → Save → canvas updates without duplicating the library row.
:::

Double-click on images may also open the editor depending on configuration.

See also: [Editor integration (vmedia)](../../../../vmedia/docs/developer/editor-integration.md) for HTTP upload/replace endpoints.
