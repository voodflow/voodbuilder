---
title: Developer
description: Extend VoodBuilder with blocks, bindings, and companion packages.
---

# Developer

This track is for **package authors** and **host-app integrators** who add blocks, binding sources, or companion products on top of VoodBuilder core.

We document **public contracts** — how markup and registration should look so blocks are selectable, configurable, and consistent between editor and public site. Internal pipeline details are intentionally omitted.

## Topics

| Guide | Summary |
|-------|---------|
| [Installation](./installation) | Wire VoodBuilder into a Laravel + Filament host |
| [Block authoring](./block-authoring) | HTML structure, dropzones, and roles |
| [Tailwind & theme tokens](./tailwind-and-theme-tokens) | Styling rules for library blocks |
| [Registering blocks](./registering-blocks) | PHP APIs for blocks and bindings |

## Core vs companion development

| Extend via | Package |
|------------|---------|
| Static blocks, conditions, channels, menu types | **Core** APIs |
| Model Integrations admin, list repeat product UI | **Dynamics** companion module |
| Reusable components library | **Components** companion |
| Full commercial module lifecycle | `registerModule()` + companion |

See [Core vs companions](../builder/core-vs-companions) for the feature matrix.

## Principles

1. **Register, don’t fork** — use `Voodbuilder::editorBlock()`, binding sources, and JS plugins.
2. **Editor ≈ public** — what authors see in the canvas should match the live site.
3. **Soft-gate paid UI** — ship commercial features in companions, not in core.
4. **Never patch** `node_modules` — extend via documented editor APIs only.
