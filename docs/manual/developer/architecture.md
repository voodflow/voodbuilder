---
title: Architecture
description: Core modules, editor shell, and rendering.
---

# Architecture

## Package layout (conceptual)

| Area | Role |
|------|------|
| **Filament resources** | Site pages, menus, chrome layouts, settings |
| **Modules** | History, Conditions, Templates, Themes, Menus, Layouts, Pages |
| **Support/Editor** | Gate, renderer, blocks, bindings, conditions, fonts bridge |
| **Licensing** | Edition capability matrix / entitlement checks |
| **resources/js/editor** | Visual editor shell (GrapesJS + VoodBuilder plugins) |
| **Contracts** | Stable extension surfaces for third parties |

## Request flows

### Public page

Request → middleware (site config) → controller → chrome layout resolution → `EditorRenderer` pipeline → Blade.

### Visual editor

Authorized `?edit=1` → EditorGate payload (labels, URLs, entitlements, flags) → Vite editor entry → `grapesjs.init` → core plugins → companion `registerPlugin` mounts.

## Soft-gates

Core may ship UI shells for companion features. When the package or capability is missing, the UI shows an upsell rather than calling missing APIs.

::: tip Screenshot needed
Diagram: Host app → Voodbuilder facade → registries → editor payload / render pipeline.
:::
