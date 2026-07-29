# Progress — Commercial plugin wave (after Phase 11)

**Last update:** 2026-07-29  
**Handoff:** see Core doc §0 + §27 in `docs/VoodBuilder_0.1.0_Modular_Architecture_and_Licensing.md`

## Push status (companions)

All companions below are on `main` @ origin, clean, **pushed**.

| Package | HEAD (local) | Notes |
|---|---|---|
| popups | `8eac12f` | Owns UI/runtime |
| components | `363fdd4` | Plugin gate + API auth fix |
| dynamic-data | `8c20ce8` | Full DD ownership |
| templates | `8ea5572` | Authoring only |
| forms | `3458e42` | Scaffold |
| cookiebar | `ed48bd2` | Scaffold |
| analitycs | `2aa3d93` | Scaffold |

Core `voodbuilder` branch `refactor/modular-architecture` must be **committed + pushed** for workstation pull (Templates bridge, UX polish, docs).

## 1. Popups closure (JS ownership)

- Moved `popups-ui.js`, `popups-runtime.js`, `popup-css-scope.js`, `popup-shell.css` into `voodflow/voodbuilder-popups`
- Core keeps stable Vite re-exports
- Commits: popups `8eac12f`, core `f3c26e0`

## 2. Components package (physical extract)

- `voodflow/voodbuilder-components` owns ComponentsModule, Editor component/global-class APIs, models, migrations
- Core uses `ComponentRuntimeBridge` for optional public CSS when the plugin is absent
- Host: `VoodbuilderComponentsPlugin::make()` + path Composer require
- Soft-gate Components tab when `componentsUrl` null
- Commits: components `363fdd4` (latest auth fix)

## 3. Dynamic Data package (physical extract)

- Repo: `voodflow/voodbuilder-dynamic-data`
- **Boundary:** all Dynamic Data behind the Filament plugin; List repeat still needs `dynamic-data.collections` (Pro+)
- Core: bridges + soft-gate Dynamic tab when plugin off
- Host: `VoodbuilderDynamicDataPlugin::make()`
- Locales: en/it/es/fr/de

## 4. Templates package (authoring plugin)

- Repo: `voodflow/voodbuilder-templates`
- **Boundary:** Core keeps list/apply + **install-from-URL** (marketplace)
- Plugin unlocks: Save, JSON import/export, multi-select
- Core: `TemplateAuthoringBridge` + soft-gate in `page-templates-sidebar.js`
- Host: `VoodbuilderTemplatesPlugin::make()`
- Locales: en/it/es/fr/de

## 5. Forms package (scaffold only — invent later)

- Repo: `voodflow/voodbuilder-forms` (pushed)
- Leave until **last** in the commercial wave

## 6. Analytics — NEXT TO IMPLEMENT

- Scaffold: `voodflow/voodbuilder-analitycs` (remote spelling)
- Next: extend existing Popups analytics toward **page** analytics

## 7. Cookiebar — after Analytics

- Scaffold on `git@voodflow-git:voodflow/voodbuilder-cookiebar.git`
- Next: real consent bar (block GA / Meta Pixel / tracking embeds; disclose media embeds; no Google Fonts dependency in Core)

## Host registration pattern

```php
->plugins([
    VoodbuilderPlugin::make(),
    VoodbuilderPopupsPlugin::make(),
    VoodbuilderComponentsPlugin::make(),
    VoodbuilderDynamicDataPlugin::make(),
    VoodbuilderTemplatesPlugin::make(),
    // VoodbuilderAnalitycsPlugin::make(), // NEXT
    // VoodbuilderCookiebarPlugin::make(),
    // VoodbuilderFormsPlugin::make(), // LAST
])
```

## Continue checklist

1. Workstation: pull Core branch + clone/update companions (§27 of architecture doc)
2. Copy `.env` block from architecture doc §27 D2 (edition + companion switches)
3. Register Filament plugins (Popups / Components / Dynamic Data / Templates)
4. Implement Analytics
5. Implement Cookiebar
6. Invent Forms
7. Optional: signed marketplace template URLs; rewrite §7 matrices
