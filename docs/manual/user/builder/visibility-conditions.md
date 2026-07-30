---
title: Visibility conditions
description: Show or hide blocks based on rules.
---

# Visibility conditions

The **Conditions** inspector tab controls whether a block renders on the public site.

## Built-in condition types (core)

- Logged in / guest
- User role
- Locale
- Route name
- Date before / after
- Custom keys registered by plugins

Group rules with **AND / OR** logic.

::: tip Screenshot needed
Conditions panel with a group: logged-in AND locale = en.
:::

## Authoring tips

- Conditions are evaluated at render time, not only in the editor preview
- Keep groups small and readable
- Plugin-specific flags appear when packages register them

Developers: [Conditions SDK](../../developer/php-sdk/conditions).
