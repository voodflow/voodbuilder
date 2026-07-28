# Plugin boundary candidates

## Official extraction order (commercial)

1. **Popups** → `voodflow/voodbuilder-popups` (first physical package)
2. **Dynamic Data** (advanced) → proprietary / Pro package boundary
3. **Components** (Agency) → proprietary boundary
4. Later: Forms, Analytics, VDocs integration, Blog, AI, Shop

## Internal modules first (same repo)

History, Conditions, Templates, Themes, Menus, Layouts, Pages — remain in Community Core package as `src/Modules/*`.

## What “optional” means before Composer split

| Feature | Today | Target |
|---|---|---|
| Popups | `config popups.enabled` + always in same package | Module + later package |
| Pages | `config pages.enabled` | Core module |
| Chrome layouts | `config chrome_layouts.enabled` | Core module |
| Components | always registered if GrapesJS on | Module + entitlement |
| Bindings | always if GrapesJS on | Module + entitlement tiers |

## Code that assumes Popups always exists

- `VoodbuilderPlugin` resource list
- ServiceProvider popup routes
- `editor/init.js` imports `registerPopupsUi`
- Public `popups-runtime` assets may still load from views — audit Blade includes before extract

## Code that assumes Components always exists

- Components + global-classes routes
- `components-ui.js` always imported from init
- Component CSS sync in page render path — must degrade gracefully

## Code that assumes advanced Dynamic Data always exists

- Bindings routes + `bindings-ui.js` import
- `ModelIntegrationResource` always registered
- Repeat/list renderers in public HTML pipeline

## Sibling plugin coupling (forbidden to deepen)

`ConfigureVtutsForVoodbuilder` and testbench autoload of `Voodflow\Vtuts\` exist for demo/host convenience. **VoodBuilder must not hard-require vtuts.** Treat as optional host integration discovered by class_exists/config only.
