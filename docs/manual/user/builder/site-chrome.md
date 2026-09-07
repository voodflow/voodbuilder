---
title: Site chrome in the editor
description: Headers, footers, and managed chrome while editing pages.
---

# Site chrome in the editor

**Site chrome** is the shared header and footer defined by a [chrome layout](../admin/chrome-layouts).

## Page editor vs layout editor

| Context | Header / footer |
|---------|-----------------|
| **Page editor** | Usually **managed** — visible, not freely editable; edit in the layout |
| **Chrome layout editor** | Fully editable; must keep the **content slot** |

::: tip Screenshot needed
Page editor with locked chrome notice and dimmed header/footer.
:::

## Navbar & footer traits

In the layout editor, select nav/footer blocks to assign:

- Logo / brand
- Menu
- CTA buttons
- Newsletter / social rows (footer variants)

Menus come from **Admin → Menus**.

## Do not put nav/footer in page templates

Starter page templates intentionally omit site chrome — the shell already provides it. Duplicating nav in page HTML causes double headers.

Related: [Chrome layouts](../admin/chrome-layouts).
