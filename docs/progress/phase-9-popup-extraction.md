# Phase 9 — Popup extraction (`voodflow/voodbuilder-popups`)

Physical Composer package extracted as a **FilamentPHP 5 plugin**, scaffolded from [filamentphp/plugin-skeleton](https://github.com/filamentphp/plugin-skeleton) `5.x`.

## Package

```text
packages/voodflow/voodbuilder-popups
├── src/VoodbuilderPopupsPlugin.php          # Filament Plugin
├── src/VoodbuilderPopupsServiceProvider.php # registers PopupsModule via Voodbuilder::registerModule()
├── src/Modules|Http|Filament|Models|…       # feature code (keeps Voodflow\Voodbuilder\* namespaces)
├── resources/views|lang
└── database/migrations
```

## Acceptance

| Requirement | Status |
|---|---|
| Core works without Popups | Popups no longer registered in Core `registerInternalModules` / `VoodbuilderPlugin` |
| Installing Popups adds features | Host requires package + `VoodbuilderPopupsPlugin::make()` |
| Uninstalling does not break Core | Core stub `popups-boot`, orphan via `Schema`/`DB`, no hard class deps |
| Existing popup data preserved | Same tables/migrations moved to plugin package |
| Public render safe if absent | Core boot stub no-ops; module not registered |
| Admin orphan warning | `PopupsOrphanStatus` + Settings notification |

## Host wiring

```php
->plugins([
    VoodbuilderPlugin::make(),
    VoodbuilderPopupsPlugin::make(),
])
```

## Follow-ups

- Physical move of `popups-ui.js` / `popups-runtime.js` into the plugin Vite build (currently gated soft in Core editor bundle).
- Own Testbench suite inside `voodbuilder-popups` (tests still run via Core TestCase + autoload-dev bridge).
