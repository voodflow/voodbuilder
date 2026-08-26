---
title: Developer manual
description: Extend VoodBuilder without forking core.
---

# Developer manual

This manual explains how **third-party packages** and the **host application** extend VoodBuilder core through public APIs.

## Principles

1. **Never edit vendor / core files** to add product features  
2. Register from your ServiceProvider `boot()` / `packageBooted()` or `Application::booting`  
3. Prefer the `Voodbuilder` facade and contracts under `Voodflow\Voodbuilder\Contracts`  
4. Keep the canvas engine **vanilla** — no patches under its `node_modules` package  
5. Put commercial JS in companion packages; talk to core via the plugin bridge  

## Start here

1. [Architecture](./architecture)  
2. [Extending overview](./extending-overview)  
3. [PHP SDK — Blocks](./php-sdk/blocks)  
4. [JS plugins](./js-plugins)  
5. [Sample plugin](./sample-plugin)  

## Companion integration (extra depth)

Third-party / companion authors should also read:

- [Developer hub](../../developer/README.md)
- [Companion integration](../../developer/companion-integration.md)
- [Sales overview](../../sales/README.md)

Internal engineering notes (audits, phase plans) live under `docs/` outside `manual/` and are not part of the product manuals.
