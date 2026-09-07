---
title: PHP SDK — Modules
description: Register full VoodBuilder modules (companion style).
---

# PHP SDK — Modules

For larger companions, implement `VoodBuilderModule` and register early:

```php
use Illuminate\Foundation\Application;
use Voodflow\Voodbuilder\Voodbuilder;

public function register(): void
{
    $this->app->booting(function () {
        Voodbuilder::registerModule(new AcmeModule(), enabled: true);
    });
}
```

Modules declare `id`, `name`, `version`, `dependencies`, `capabilities`, and `register` / `boot` with `ModuleContext`.

Optional capability interfaces: `RegistersBlocks`, `RegistersRoutes`, `RegistersAssets`, `RegistersConditions`, `RegistersFilamentResources`, `RegistersEditorPanels`, `RegistersEditorCommands`.

Core internal modules: History, Conditions, Templates, Themes, Menus, Layouts, Pages.
