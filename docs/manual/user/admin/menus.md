---
title: Menus
description: Build nested navigation for headers and footers.
---

# Menus

**Admin → Menus** stores navigation trees used by site chrome (navbar / footer) and menu-driven blocks.

## Item types (core)

| Type | Behaviour |
|------|-----------|
| **URL** | Absolute or relative link |
| **App route** | Named Laravel route from the host catalog |
| **Plugin types** | Extra types registered by packages (e.g. docs nodes) |

Items support nesting (parent / children), labels, icons (Tabler set where enabled), and open-in-new-tab style options depending on the form.

## Workflow

1. Create a menu (e.g. `main`, `footer`).
2. Add items; drag to nest when the UI allows.
3. Assign the menu in a chrome navbar/footer block traits (in the chrome layout editor).

## Clone & translate

Use clone / translation actions when multi-locale is enabled so each locale can keep its own tree.

::: tip Screenshot needed
Menu edit screen with nested items and type selector.
:::
