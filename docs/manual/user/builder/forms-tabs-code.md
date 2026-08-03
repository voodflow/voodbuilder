---
title: Forms, tabs & code
description: Optional editor plugins bundled with the page builder.
---

# Forms, tabs & code

Optional editor plugins (enabled in config by default):

| Plugin | Use |
|--------|-----|
| **Forms** | Form blocks; submissions handled by editor form endpoints / events |
| **Tabs** | Tabbed content blocks + front-end runtime |
| **Custom code** | Embed HTML/JS snippets (use carefully) |
| **Style background** | Richer background style controls |

Host apps can disable plugins in `config/voodbuilder.php` → `editor.plugins` (legacy key `grapesjs.plugins` still accepted).

::: tip Screenshot needed
Forms block selected with field traits in Content panel.
:::

A dedicated **Forms** product companion (`voodflow/vforms`) may extend this; basic form blocks still ship with core.
