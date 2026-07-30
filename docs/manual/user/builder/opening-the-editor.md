---
title: Opening the editor
description: Enter and leave the visual builder safely.
---

# Opening the editor

## Ways to open

1. **Admin** — Site Pages → **Open visual editor**
2. **Public URL** — append `?edit=1` while authenticated with edit permission
3. **Chrome layouts** — open from the Layouts resource (layout editor mode)

## Editor modes

| Mode | What you edit |
|------|----------------|
| **Page** | A site page body (managed chrome may be locked) |
| **Chrome layout** | Header, content slot, footer shell |
| **Popup** | Soft-gated — requires the Popups companion |

## Chrome while editing a page

On pages that use a chrome layout, header/footer often appear as a **managed shell**: visible for context, editable only in the layout editor. A notice explains when chrome is locked.

## Save & exit

- **Save** persists HTML/CSS/project data for the page or layout
- **View page** / exit leaves `?edit=1`
- Use **undo / redo** before saving when experimenting

::: tip Screenshot needed
Top canvas bar with Save, View page, device breakpoints, and light/dark toggle.
:::

Related: [Canvas & toolbar](./canvas-and-toolbar), [Site chrome](./site-chrome).
