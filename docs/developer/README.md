# VoodBuilder — developer hub

Technical entry for host apps and companion package authors.

## Start here

1. [Installation](../manual/developer/installation.md)
2. [Architecture](../manual/developer/architecture.md)
3. [Extending overview](../manual/developer/extending-overview.md)
4. [Companion integration](./companion-integration.md) ← **required for third parties**
5. [Dynamic SitePages](./dynamic-site-pages.md) ← template pages that claim companion routes
6. [Events & hooks](../manual/developer/events-and-hooks.md)
7. [JS plugins](../manual/developer/js-plugins.md)
8. [Sample plugin](../manual/developer/sample-plugin.md)
9. [Config reference](../manual/developer/config-reference.md)

## Extension map

| Surface | Entry |
|---------|-------|
| Filament admin | `Voodflow\Voodbuilder\VoodbuilderPlugin` |
| Service provider | `VoodbuilderServiceProvider` |
| Public facade | `Voodflow\Voodbuilder\Voodbuilder` |
| Modules | `Voodbuilder::registerModule(VoodBuilderModule $m)` |
| Editor blocks | `editorBlock` / `editorRichContentBlock` / `editorServerBlock` |
| Bindings / repeats | `editorBindingSource` / `editorRepeatList` |
| Conditions / labels | `editorCondition` / `editorLabels` |
| Content channels | `contentChannel` |
| Dynamic SitePages | `dynamicPageProvider` → [dynamic-site-pages.md](./dynamic-site-pages.md) |
| Menus | `menuItemType` |
| Themes / fonts | `subTheme` / `registerFonts` |
| Entitlements | `can` / `cannot` |
| JS bridge | `window.VoodbuilderEditor.registerPlugin` |

PHP SDK detail: [`../manual/developer/php-sdk/`](../manual/developer/php-sdk/blocks.md).

## Publishing & assets

```bash
php artisan voodbuilder:install
php artisan vendor:publish --tag=voodbuilder-config
# Vite entries / npm sync are handled by install + SyncNpmDeps
```

Host `config/voodbuilder.php` and optional `config/voodbuilder-integrations.php` for host-owned registrations.

**Never** publish patches into `node_modules/grapesjs`. Custom editor behaviour belongs in:

- `packages/voodflow/voodbuilder/resources/js/editor/` (or companion `resources/js/editor/`)
- `packages/voodflow/voodbuilder/src/Support/Editor/`
- Companion ServiceProviders calling the facade

## Namespaces

- PHP: `Voodflow\Voodbuilder\…`
- Views: `voodbuilder::…`
- Config: `config('voodbuilder.*')`
- Contracts: `Voodflow\Voodbuilder\Contracts\…`

## Do / don’t

**Do**

- Register from `Application::booting()` / `packageBooted()` before module boot
- Soft-gate paid UI with entitlements + `class_exists` for companion packages
- Keep GrapesJS version pinned in root `package.json` (semver range, not `*`)
- Smoke-test `?edit=1` after upgrading GrapesJS

**Don’t**

- Patch `node_modules/grapesjs` or other editor npm packages
- Fork core for a single block
- Hard-code edition/SKU strings — use entitlements
- Put commercial JS into core — ship companions + plugin bridge

## Related

- Sales: [`../sales/README.md`](../sales/README.md)
- Engineering notes outside manuals: [`../EDITOR.md`](../EDITOR.md), [`../SDK_PLUGIN_API.md`](../SDK_PLUGIN_API.md)
- Release: [`../RELEASE_CHECKLIST.md`](../RELEASE_CHECKLIST.md)
