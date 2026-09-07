---
title: Getting Started
description: What VoodBuilder is and how the academy is organized.
---

# Getting Started

VoodBuilder adds a **visual page builder** to Laravel applications that use Filament. You manage structure in the admin panel; you design pages in the **visual editor**.

## What is included in core

| Area | Core capability |
|------|-----------------|
| **Site pages** | Create pages with the rich editor or the visual builder |
| **Menus** | Nested navigation for headers and footers |
| **Chrome layouts** | Shared header, content slot, and footer shells |
| **Visual editor** | Layout elements, section catalog, styling, conditions, revisions |
| **Publishing** | Save editor content and render it on the public site |
| **Global text tags** | Site-wide placeholders like `{brand_name}` and `{current_year}` |

## What requires a companion

| Feature | Companion package |
|---------|-------------------|
| Model integrations admin UI, **Make dynamic**, list repeat | **Dynamics** (`voodflow/voodbuilder-dynamic-data`) |
| Save reusable components, global classes | **Components** |
| Save / import / export templates as JSON | **Templates Pro** |
| Popup library and runtime | **Popups** |
| Premium section catalog packs | **Elements** (planned) |

Companion licensing is handled separately — core installs with a standard Composer require.

## Recommended learning path

1. [Installation](./installation) — add VoodBuilder to your project  
2. [Interface tour](./interface-tour) — admin resources and editor layout  
3. [Your first page](./your-first-page) — publish a simple landing section  
4. [Site settings](./site-settings) — brand, appearance, SEO  
5. Continue in the [Builder](../builder/) track for pages, menus, layouts, and dynamic content
