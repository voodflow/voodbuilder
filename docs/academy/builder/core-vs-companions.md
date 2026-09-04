---
title: Core vs companions
description: Which features ship with VoodBuilder core and which need a companion package.
---

# Core vs companions

VoodBuilder **core** (`voodflow/voodbuilder`, MIT) is a complete visual site builder. Paid **companions** add specialised workflows; the UI **soft-gates** missing companions instead of breaking.

## Core (free)

| Feature |
|---------|
| Site pages (rich + visual builder) |
| Menus |
| Chrome layouts |
| Settings (brand, appearance, SEO) |
| Media library + Asset Manager |
| Layout elements + section catalog |
| Style panel (Tailwind utilities) |
| Content traits (static text, images, menus on chrome) |
| Global text tags |
| Visibility conditions |
| Page template **apply** |
| Revisions |
| Forms / tabs / code plugins (when enabled in config) |
| In-canvas image editing |
| PHP/JS extension registration APIs |

## Companion packages

| Companion | Unlocks |
|-----------|---------|
| **Dynamics** (`voodbuilder-dynamic-data`) | Model Integrations admin, Make dynamic, list repeat collections |
| **Components** | Save reusable components, global classes library |
| **Templates Pro** | Save templates, JSON import/export, multi-select authoring |
| **Popups** | Popup library and public popup runtime |
| **Elements** (planned) | Premium section catalog packs |

## What you see without a companion

- Tabs or buttons show a **marketing empty state** with install / licence CTA
- Core routes and public pages continue to work
- Already-saved dynamic markup still renders on the public site if Dynamics was previously active

## Licensing

Core installs with:

```bash
composer require voodflow/voodbuilder
```

Companions require their own Composer package and licence activation — detailed licensing docs will follow.

## Documentation map

| Topic | Section |
|-------|---------|
| Model integrations | [Builder → Model integrations](./model-integrations) |
| List repeat | [Builder → Lists & collections](./lists-and-collections) |
| Block authoring | [Developer → Block authoring](../developer/block-authoring) |
