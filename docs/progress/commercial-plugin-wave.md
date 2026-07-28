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

## 3. Dynamic Data collections package (physical extract)

- Repo: `voodflow/voodbuilder-dynamic-data`
- **Boundary:** Community `dynamic-data.single` stays in Core; Pro `dynamic-data.collections` in this plugin
- Module ID: `dynamic_data_collections` (`DynamicDataCollectionsModule`) — does **not** replace Core `dynamic_data`
- Owned PHP (classmap): RepeatRenderer, ListResolver, RepeatListRegistry, RelationFilters, SortFields, ItemBindingSource
- Core: `DynamicDataCollectionsBridge`, gated catalog/preview/`BindingRenderer`, registrar registers `.item` only when module enabled
- Editor JS: `dynamicDataCollections` soft-gates List repeat UI in `bindings-ui.js`
- Host: `VoodbuilderDynamicDataPlugin::make()` + Composer path require
- Locales: en/it/es/fr/de

## 4. Forms package (scaffold only — invent later)

- Repo: `voodflow/voodbuilder-forms` (pushed)
- No Core Forms module yet; leave until last in the commercial wave

## 5. Analytics

- Scaffold: `voodflow/voodbuilder-analitycs` (remote spelling)
- Next: extend existing Popups analytics toward **page** analytics

## 6. Cookiebar

- Scaffold on `git@voodflow-git:voodflow/voodbuilder-cookiebar.git`
- Next: real consent bar (block GA / Meta Pixel / tracking embeds; disclose media embeds; no Google Fonts dependency in Core)

## Host registration pattern

```php
->plugins([
    VoodbuilderPlugin::make(),
    VoodbuilderPopupsPlugin::make(),
    VoodbuilderComponentsPlugin::make(),
    VoodbuilderDynamicDataPlugin::make(),
    // VoodbuilderFormsPlugin::make(), // invent later
])
```
