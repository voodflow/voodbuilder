---
title: Chrome layouts
description: Shared header, content slot, and footer wrappers.
---

# Chrome layouts

A **chrome layout** is a visual template for the site shell:

```
┌─────────────────────────────────────┐
│  Header (nav, logo, …)              │
├─────────────────────────────────────┤
│  Page content slot                  │
├─────────────────────────────────────┤
│  Footer                             │
└─────────────────────────────────────┘
```

Managed under **Admin → Layouts** (Chrome layouts).

## Content slot

Every chrome layout must keep exactly one **page content slot** marker. On the public site that marker is replaced by the page or plugin body (`@yield('content')` / channel sections).

::: warning
Do not delete the content slot from the layout. Without it, plugin pages cannot inject their body into the shell.
:::

## Channels & default

Resolution order:

1. Enabled **non-default** layout whose channels include the current content channel  
2. Peer channel fallback (e.g. docs ↔ tutorials) when configured  
3. Enabled layout marked **default** (catch-all; its channel list is ignored)  
4. Classic app layout without chrome

Assign channels on the layout form. Mark at most one layout as default.

## Content max-width

Layouts can define a **custom content max-width**. The editor’s content-width toolbar can cycle **Full → Normal (80rem) → Custom** when a custom value exists. See [Content width](../builder/content-width).

## Editing visually

Open the chrome layout in the visual editor (layout mode). Edit header/footer there; page editors see managed chrome as locked previews.

::: tip Screenshot needed
Chrome layout editor highlighting the content slot between header and footer.
:::

::: tip Short video needed (~20s)
Create layout → drop navbar + content slot + footer → assign as default → view a page.
:::
