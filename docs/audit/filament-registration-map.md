# Filament registration map

## Plugin entry

`Voodflow\Voodbuilder\VoodbuilderPlugin`

### Resources (conditional)

| Resource | Config gate | Module |
|---|---|---|
| `NavigationMenuResource` | always | Menus |
| `ModelIntegrationResource` | always | Dynamic Data |
| `SitePageResource` | `voodbuilder.pages.enabled` | Pages |
| `PopupResource` | `voodbuilder.popups.enabled` | Popups |
| `ChromeLayoutResource` | `voodbuilder.chrome_layouts.enabled` | Layouts |

### Pages

| Page | Module |
|---|---|
| `VoodbuilderSettingsPage` | Settings |

### Other Filament wiring

| Item | Where | Module |
|---|---|---|
| `AdminDatabaseNotifications` | plugin `databaseNotifications` | Core |
| `CookieConsentPlugin` | registered/booted by VoodbuilderPlugin | Core integration |
| `ThemesWorkspace` Livewire | ServiceProvider | Themes |
| `ThemeMapBridge` Livewire | ServiceProvider | Themes |
| Menu tree assets | `FilamentMenuTreeAssets` | Menus |
| Admin CSS/JS | `FilamentAdminAssets` | Core |
| Theme map assets | `ThemeMapAssets` | Themes |

### Rich content custom blocks

Registered on `RichContentBlockRegistry` in ServiceProvider (`HeroBlock`, landing blocks, config-driven). Belongs to **Editor / Pages** legacy Filament rich content path (parallel to GrapesJS).

## Target

`RegistersFilamentResources` per module; plugin becomes thin and asks `ModuleRegistry` for contributions. Config flags remain as entitlement/module enable mirrors during transition.
