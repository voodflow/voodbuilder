---
title: Opening the editor
description: Enter and leave the visual builder safely.
---

# Opening the editor

## Ways to open (core)

| Method | Steps |
|--------|-------|
| **Admin** | Site Pages → **Open visual editor** |
| **Public URL** | Append `?edit=1` while authenticated with edit permission |
| **Chrome layouts** | Layouts resource → open layout in editor mode |

Always prefer the admin action when available — it keeps permissions and URLs correct.

## Editor modes

| Mode | What you edit | Package |
|------|---------------|---------|
| **Page** | Site page body | **Core** |
| **Chrome layout** | Header, content slot, footer | **Core** |
| **Popup** | Overlay content | **Popups companion** |

## Chrome while editing a page (core)

Pages that use a chrome layout often show header and footer as a **managed shell**:

- Visible for context
- Not freely editable on the page canvas
- Edited only in the **layout editor**

A notice explains when chrome is locked.

## Save and exit (core)

- **Save** — persists HTML, CSS, and project data
- **View page** — leaves edit mode (`?edit=1` removed)
- **Undo / redo** — experiment before saving

Related: [Canvas & inspector](./canvas-and-inspector), [Chrome layouts](./chrome-layouts)
