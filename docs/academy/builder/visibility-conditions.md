---
title: Visibility conditions
description: Show or hide blocks based on rules evaluated at render time.
---

# Visibility conditions

The **Conditions** inspector tab controls whether a block appears on the **public site**. **Core.**

## Built-in condition types

| Type | Example |
|------|---------|
| Logged in / guest | Show CTA only for guests |
| User role | Show admin link for editors |
| Locale | Banner on `it` only |
| Route name | Highlight on checkout routes |
| Date before / after | Seasonal promo section |
| Custom keys | Registered by companion plugins |

Combine rules with **AND / OR** groups.

## Authoring tips

- Conditions evaluate at **render time** — preview in the editor may differ slightly from the public page
- Keep groups small and readable
- Use conditions for **whole sections**; use binding hide-when-empty for single dynamic fields (**Dynamics**)

## Common patterns

| Goal | Approach |
|------|----------|
| Hero for logged-in users only | Condition: logged in |
| Hide empty greeting | Binding with hide-when-empty **(Dynamics)** or condition on parent |
| Locale-specific promo | Condition: locale equals `en` |

Developers can register custom condition evaluators from companion packages.

Related: [Model integrations](./model-integrations), [Publishing & preview](./publishing-and-preview)
