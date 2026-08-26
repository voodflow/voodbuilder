---
title: VoodBuilder manuals
description: User and developer documentation for the VoodBuilder core package.
---

# VoodBuilder manuals

Welcome to the official **VoodBuilder** documentation. These manuals cover the **core package only** (`voodflow/voodbuilder`). Companion products (Components, Dynamics, Templates Pro, Popups, and others) will be documented in separate manuals later.

| Manual | Audience | Start here |
|--------|----------|------------|
| [User manual](./user/) | Editors, marketers, site admins | [Getting started](./user/getting-started) |
| [Developer manual](./developer/) | Package authors & host-app integrators | [Extending VoodBuilder](./developer/extending-overview) |

Also see the package docs hub: [sales](../sales/README.md), [developer companions](../developer/README.md), [index](../README.md).

## Importing into VitePress / vdocs

This folder is plain Markdown with VitePress-friendly conventions:

- YAML frontmatter (`title`, `description`)
- Relative links between pages
- Custom containers (`::: tip`, `::: warning`, `::: info`) for callouts and media placeholders
- A ready-to-merge sidebar in [`sidebar.ts`](./sidebar.ts)

Point your VitePress `srcDir` (or a vdocs content channel) at `packages/voodflow/voodbuilder/docs/manual`, or copy/symlink these files into your docs package. Merge `sidebar.ts` into the site config.

::: tip Screenshots and videos
Where a visual helps, you will see a **Screenshot needed** or **Short video needed** callout. Assets will be added in a follow-up pass — keep the placeholders so layout and captions stay stable.
:::

## Scope of “core”

**Included today:** site pages, menus, chrome layouts, visual themes/settings, the visual editor shell (layout, sections, style, fonts, conditions, revisions, page-template apply, forms/tabs/code plugins), public rendering, and the PHP/JS extension SDK.

**Soft-gated / companion (stubs only):** Components library authoring (user-saved reusable pieces — **not** the product block catalog), Dynamic Data Pro, Templates authoring extras, Popups. Planned: **Elements** pack (premium BlockManager sections) — see [`../handoff/elements-companion-pack.md`](../handoff/elements-companion-pack.md). Core may show an upsell when those packages are missing — full docs land with each companion.
