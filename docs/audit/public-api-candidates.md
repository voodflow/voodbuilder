# Public API candidates

APIs that are already effectively public for host apps / integrators and should become stable contracts in 0.1.0.

## PHP — keep stable

| API | Notes |
|---|---|
| `VoodbuilderPlugin::make()` | Filament registration |
| `config/voodbuilder.php` keys | Document freeze; additive changes only |
| `Voodbuilder::contentChannel()` | Channel registration |
| `ContentChannelRegistry` / `PublicContentChannel` | Channel plugins |
| `MenuItemTypeRegistry` / `MenuItemTypeHandler` | Custom menu item types |
| `GrapesJsBlockRegistry` + block contracts | Custom blocks |
| `BindingRegistry` / `GrapesJsBindingSource` | Custom binding sources |
| `SubThemeRegistry` | Theme discovery |
| `ModelRegistry` / `ReverseRelationRegistry` | Model integration helpers |
| `IntegrationRegistrar` | Host boot hooks |
| Facade-style helpers used in Blade | Treat as public render API |

## PHP — internal (do not promise)

Concrete normalizers, HTML pipelines, gate classes, Filament form builders, most `Support/GrapesJs/*` helpers until wrapped.

## HTTP JSON endpoints

Editor endpoints under the GrapesJS route group are a de-facto API for the JS editor. Version or document payloads before external SDK use.

## JS — candidate SDK surface

| Surface | Status |
|---|---|
| Block settings registry (`registerBlockSettings`) | Good SDK seed |
| `editor-api.js` fetch helpers | Internal |
| Custom events `voodbuilder:*` | Document as extension bus |
| Command ids `voodbuilder:*` | Document after registry |

## Capability API (to add)

```php
VoodBuilder::can('components.export');
```

Not present yet; introduced Phase 7 without scattering plan checks.
