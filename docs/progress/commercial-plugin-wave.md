# Progress — Commercial plugin wave (after Phase 11)

## 1. Popups closure (JS ownership)

- Moved `popups-ui.js`, `popups-runtime.js`, `popup-css-scope.js`, `popup-shell.css` into `voodflow/voodbuilder-popups`
- Core keeps stable Vite re-exports
- Commits: popups `8eac12f`, core `f3c26e0`

## 2. Components package (physical extract)

- `voodflow/voodbuilder-components` owns ComponentsModule, GrapesJS component/global-class APIs, models, migrations
- Core uses `ComponentRuntimeBridge` for optional public CSS when the plugin is absent
- Host: `VoodbuilderComponentsPlugin::make()` + path Composer require
- Commits: components `8cb0ad8`, core `553f56f`

## 3. Dynamic Data package (scaffold + boundary)

- Repo: `voodflow/voodbuilder-dynamic-data` (pushed)
- **Boundary:** Community `dynamic-data.single` + bindings stay in Core; this plugin will own Pro/Agency **collections** and related Filament/editor surfaces
- Physical move of collections-only code is the next implementation slice

## 4. Forms package (scaffold)

- Repo: `voodflow/voodbuilder-forms` (pushed)
- Stub module + locales en/it/es/fr/de; extract after Forms audit in Core

## 5. Analytics + Cookiebar scaffolds

- `voodflow/voodbuilder-analitycs` pushed to voodflow-git (folder spelling matches remote)
- `voodbuilder-cookiebar` local scaffold ready; **GitHub remote push failed** (`Repository not found` for `git@github.com:voodflow/voodbuilder-cookiebar.git`) — create the GitHub repo or fix access, then push

## Host registration pattern

```php
->plugins([
    VoodbuilderPlugin::make(),
    VoodbuilderPopupsPlugin::make(),
    VoodbuilderComponentsPlugin::make(),
    // VoodbuilderDynamicDataPlugin::make(), // when collections extract lands
    // VoodbuilderFormsPlugin::make(),
])
```
