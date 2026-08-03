---
title: Image editing
description: In-canvas crop and filters with the image editor.
---

# Image editing

When enabled, selecting an image (or background-image section) offers **Edit image** on the canvas toolbar. The editor opens an in-canvas image tool (Jodit image editor) for crop / filters; saving uploads the result and updates `src` / background attributes.

Toggle via config / env (`editor.image_editor` / `VOODBUILDER_EDITOR_IMAGE_EDITOR`; legacy key `grapesjs.image_editor`).

::: tip Short video needed (~20s)
Select image → Edit image → crop → save → canvas updates.
:::

Double-click behaviour on images may also open related editing flows depending on configuration.
