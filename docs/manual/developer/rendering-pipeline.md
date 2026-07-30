---
title: Rendering pipeline
description: How editor HTML becomes the public page.
---

# Rendering pipeline

Public HTML passes through `EditorRenderer` (order conceptual):

1. Strip chrome bleed / editor-only markers as needed  
2. Sanitize  
3. Migrate theme tokens  
4. Normalize code / tabs / forms  
5. Resolve server/dynamic placeholders  
6. Resolve bindings  
7. Evaluate visibility conditions  
8. Replace global text tags  

Chrome layouts resolve via `ChromeLayoutRenderer` + content slot substitution for plugin bodies.

## Implications for plugin authors

- Store only data you are willing to sanitize  
- Prefer theme tokens that survive migration  
- Binding `resolve()` must be fast and null-safe  
- Condition handlers must be pure regarding request state they need  

::: tip Screenshot needed
Sequence diagram of EditorRenderer stages.
:::
