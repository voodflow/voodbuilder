---
title: Publishing & preview
description: From editor save to the public page.
---

# Publishing & preview

## Preview in the editor

Use device breakpoints, light/dark toggle, and preview controls on the canvas. Dynamic bindings and some conditions may differ slightly from the fully rendered public page.

## Public render

On save, VoodBuilder stores editor HTML/CSS/project data. The public pipeline sanitizes markup, migrates theme tokens, resolves bindings, evaluates conditions, normalizes forms/tabs/code, and replaces global text tags.

## Checklist before go-live

1. Save in the editor  
2. View the page **without** `?edit=1`  
3. Check mobile breakpoint  
4. Verify chrome (header/footer) and content width  
5. Confirm forms, links, and localized variants  

::: tip Screenshot needed
Same page in editor vs public tab side by side.
:::
