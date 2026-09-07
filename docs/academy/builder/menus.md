---
title: Menus
description: Build navigation and assign it to header and footer blocks.
---

# Menus

**Admin → Menus** stores navigation trees used by site chrome and some menu-driven blocks. **Core.**

## Item types

| Type | Behaviour |
|------|-----------|
| **URL** | Absolute or relative link |
| **App route** | Named Laravel route from the host catalog |
| **Plugin types** | Extra types registered by packages (e.g. Documentation links when vdocs is installed) |

Items support **nesting** (parent / children), labels, icons, and open-in-new-tab options depending on your form configuration.

## Workflow

1. **Create a menu** — common handles are `main` (header) and `footer`.
2. **Add items** — URLs, routes, or plugin types; nest children under parents.
3. **Assign the menu** in the chrome layout editor:
   - Select the **Navbar** block → Content tab → choose the menu.
   - Select **Footer** variants → assign footer menus or link columns as provided by the block.

Menus live in the **layout**, not inside individual pages. One menu update refreshes every page that uses that chrome layout.

## Position in the layout

```
Chrome layout
├── Navbar block  ← menu: main
├── Content slot  ← page body (no menu here)
└── Footer block  ← menu: footer (or link columns)
```

Edit the layout visually (**Admin → Layouts → Open visual editor**) to wire menus to the correct blocks.

## Clone and translate (core)

When locales are enabled, clone or translate menus so each language has its own labels and URLs. Keep structure parallel across locales when possible.

## Tips

- Use **App route** items for internal pages that might change slug — the route name stays stable.
- Limit top-level items for mobile; nest secondary links under parent items.
- For documentation sites, add a **Documentation** menu item type (vdocs) instead of hard-coding `/docs` URLs.

Related: [Chrome layouts](./chrome-layouts), [Pages & layouts](./pages-and-layouts)
