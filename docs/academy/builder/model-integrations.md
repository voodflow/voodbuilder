---
title: Model integrations
description: Connect Eloquent models to the visual editor for live dynamic fields.
---

# Model integrations

**Model integrations** let you bind headings, links, images, and buttons to live database content — resolved on every page view, not baked in at save time.

::: info Package
The admin UI (**Model Integrations** resource), **Make dynamic** picker, and binding resolution UX require the **Dynamics** companion (`voodflow/voodbuilder-dynamic-data`). Core ships the render contracts; without Dynamics you see a soft gate in the Dynamic tab.
:::

## What is a model integration?

A model integration registers an Eloquent model (e.g. `User`, `Article`, `Product`) with:

- A short **alias** used in the editor (e.g. `users`, `articles`)
- An **allowlist** of fields authors can bind (title, image, URL, …)
- Optional scopes (published only, public records, …)

Create integrations under **Admin → Model Integrations** when Dynamics is installed.

## Three data contexts

For each integrated model, authors can bind three contexts:

| Context | Meaning | Example use |
|---------|---------|-------------|
| **Authenticated** (`{alias}.auth`) | Current logged-in user | “Hello, {name}” in a header |
| **Latest** (`{alias}.latest`) | Newest published record | Hero featuring the latest article |
| **List item** (`{alias}.item`) | Current row inside a repeated list | Card title in a grid |

Examples:

- Greeting: bind `users.auth.name` to a heading — shows the visitor’s name when logged in
- Spotlight: bind `articles.latest.title` to a hero heading — shows the newest article
- Grid: inside a list repeat, bind `articles.item.title` on each card

## Editor workflow

1. Open a visual page with `?edit=1`.
2. Select an element (heading, paragraph, link, image, button).
3. Open the **Dynamic** tab → **Make dynamic**.
4. Choose the model and field (or use Rich Text dynamic tag picker).
5. Save the page.

| Element | Binding effect |
|---------|----------------|
| Text nodes | Replace inner text with live value |
| Links / buttons | Label and/or `href` independently |
| Images | Set `src` (and `alt` when configured) |

Remove a binding: select element → **Clear dynamic binding**.

## Hide empty values

Bindings can hide themselves when empty (e.g. guest user name). Use **visibility conditions** for whole sections — see [Visibility conditions](./visibility-conditions).

## Global text tags vs model bindings

| Need | Use | Package |
|------|-----|---------|
| Year, brand, site URL, quick username hello | `{current_year}`, `{logged_username}`, … | **Core** — [Global text tags](./global-text-tags) |
| Article title, product price, custom model fields | Make dynamic | **Dynamics** |
| Hide section for guests | Conditions | **Core** |

## Plugin-provided sources

Companion packages can register additional binding sources (e.g. “current exhibitor”, site settings). They appear alongside model integrations in the same picker.

Next: [Lists & collections](./lists-and-collections)
