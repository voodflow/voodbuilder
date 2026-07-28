# PHP module map (as-is)

Namespaces live under `Voodflow\Voodbuilder\`. Proposed target folders are `src/Modules/<Name>/` (not created yet).

## Core / bootstrap

| Class / area | Role |
|---|---|
| `Voodbuilder` | Facade-ish helpers (content channels, etc.) |
| `VoodbuilderServiceProvider` | All registration |
| `VoodbuilderPlugin` | Filament panel plugin |
| `Support/License/VoodbuilderLicense` | Binary licence key check |

## Pages

| Artifact | Path |
|---|---|
| Model | `Models/SitePage` |
| Public controller | `Http/Controllers/SitePageController`, `HomeController` |
| Editor save | `Http/Controllers/GrapesJsPageController` |
| Support | `Support/SitePage*`, `Support/PageBuilderAccess` |

## Layouts (Chrome)

| Artifact | Path |
|---|---|
| Model | `Models/ChromeLayout` |
| Filament | `Filament/Resources/ChromeLayoutResource` |
| Editor | `ChromeLayoutEditorController`, `GrapesJsChromeLayoutController` |
| Support | `Support/ChromeLayout*`, `Support/GrapesJs/GrapesJsChrome*` , SiteNav/SiteFooter blocks |

## Menus

| Artifact | Path |
|---|---|
| Models | `NavigationMenu`, `NavigationMenuItem` |
| Filament | `NavigationMenuResource` |
| Support | `Support/Navigation*`, `MenuItemTypeRegistry`, `MenuRouteCatalog` |

## Themes

| Artifact | Path |
|---|---|
| Livewire | `Filament/Livewire/ThemesWorkspace`, `ThemeMapBridge` |
| Support | `SubTheme*`, `ActiveThemeMap`, `ThemeMapAssets`, palette helpers |
| Frontend | `resources/js/theme-map/` |

## Dynamic data

| Artifact | Path |
|---|---|
| Model | `ModelIntegration` |
| Filament | `ModelIntegrationResource` |
| Support | `Support/GrapesJs/Bindings/*` |
| HTTP | `GrapesJsBindingsController`, preview/media controllers |

## Conditions

| Artifact | Path |
|---|---|
| Support | `Support/GrapesJs/Conditions/*` |

## History

| Artifact | Path |
|---|---|
| Model | `SitePageRevision` |
| HTTP | `GrapesJsPageRevisionsController` |
| Support | `Support/GrapesJs/SitePageRevisionRecorder` |

## Templates

| Artifact | Path |
|---|---|
| Model | `PageTemplate` |
| HTTP | `GrapesJsPageTemplatesController` |
| Support | `GrapesJsPageTemplate*`, `StarterPageTemplates`, landing section HTML builders |

## Components

| Artifact | Path |
|---|---|
| Model | `BuilderComponent`, `BuilderGlobalClass` |
| HTTP | `GrapesJsComponentsController`, `GrapesJsGlobalClassesController` |
| Support | `GrapesJsComponent*` |

## Popups

| Artifact | Path |
|---|---|
| Models | `BuilderPopup`, `BuilderPopupEvent` |
| Filament | `PopupResource` |
| HTTP | popup CRUD, content, public data, analytics, editor |
| Support | `Support/GrapesJs/Popups/*`, `GrapesJsPopupEditorGate` |

## Settings / site chrome runtime

| Artifact | Path |
|---|---|
| Settings model | `VoodbuilderSettings` |
| Filament page | `VoodbuilderSettingsPage` |
| Middleware | `ApplyVoodbuilderSiteConfig` |
| Public extras | Auth/Account/Search controllers, notification Livewire |

## Direct instantiation hotspots

`VoodbuilderServiceProvider` constructs registries and calls static registrars (`IntegrationRegistrar`, landing section classes, `BuiltinBindingSources`). Feature code often `new`s or resolves concrete GrapesJS support classes rather than interfaces — full list refined during Phase 3 when introducing adapters.
