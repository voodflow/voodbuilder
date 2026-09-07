---
title: Publishing & preview
description: From editor save to the live public page.
---

# Publishing & preview

## Preview in the editor (core)

Use canvas controls before go-live:

- Device breakpoints (desktop / tablet / mobile)
- Light / dark toggle
- Outline / drop-zone helpers

Dynamic bindings and some conditions may differ slightly between editor preview and the fully rendered public page — always verify without `?edit=1`.

## Save behaviour (core)

**Save** stores editor HTML, CSS, and project metadata. The public pipeline then:

- Sanitizes markup
- Resolves dynamic bindings **(Dynamics)**
- Evaluates visibility conditions **(core)**
- Replaces global text tags **(core)**
- Normalises forms, tabs, and code blocks

## Go-live checklist

1. **Save** in the editor
2. Open the page **without** `?edit=1`
3. Check **mobile** breakpoint
4. Verify **chrome** (header/footer) and **content width**
5. Test **links**, **forms**, and **locale variants**
6. For dynamic pages, confirm bindings with real data (logged in vs guest, empty lists)

## Revisions (core)

VoodBuilder keeps revision history for visual pages. Roll back from the admin page form when an experiment goes wrong.

## Page publish state

Ensure the **Site Page** record is published (or your host app’s equivalent status) — saving the editor does not always imply the page is publicly reachable if draft mode is enabled.

Related: [Translations](../getting-started/translations), [Model integrations](./model-integrations)
