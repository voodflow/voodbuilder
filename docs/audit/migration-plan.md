# Migration plan — current implementation → target modules

**Source:** `docs/VoodBuilder_0.1.0_Modular_Architecture_and_Licensing.md`  
**Package:** `voodflow/voodbuilder` only  
**Constraints:** no Filament core patches, no Editor core patches, no hard-wired deps on sibling plugins (e.g. vtuts)

## Goal

Move from a monolith (`VoodbuilderServiceProvider` + large Editor `editor/init.js` wiring) to internal modules behind stable contracts, without changing behaviour for `0.0.11` data.

## Current → target module map

| Target module | Current PHP locus | Current JS locus | Tables | Filament | Extract priority |
|---|---|---|---|---|---|
| **Core / Settings** | `VoodbuilderSettings`, `VoodbuilderSettingsPage`, `ApplyVoodbuilderSiteConfig`, SEO helpers | — | `voodbuilder_settings` | Settings page | Stay Core |
| **Pages** | `SitePage`, `SitePageController`, `EditorPageController`, resolvers | `editor/init.js` page mode, `page-tailwind-autobuild.js` | `site_pages` | `SitePageResource` | Late (Phase 6 #7) |
| **Layouts (Chrome)** | `ChromeLayout*`, chrome gates, nav/footer blocks | `editor-chrome*.js`, `chrome/` | `voodbuilder_chrome_layouts` | `ChromeLayoutResource` | Phase 6 #6 |
| **Menus** | `NavigationMenu*`, menu registries, placements | chrome nav/footer slots | `voodbuilder_menus`, `voodbuilder_menu_items` | `NavigationMenuResource` | Phase 6 #5 |
| **Themes** | `SubTheme*`, `ActiveThemeMap`, ThemeMap Livewire/React | `resources/js/theme-map/` | filesystem + settings | Themes workspace | Phase 6 #4 |
| **Editor** | block registries, assets, gates | entire `resources/js/editor/` | — | editor blades | Continuous (Phase 5) |
| **Dynamic Data** | `Support/Editor/Bindings/*`, `ModelIntegration*` | `bindings-ui.js` (~100KB) | `voodbuilder_model_integrations` | `ModelIntegrationResource` | Commercial boundary later |
| **Conditions** | `Support/Editor/Conditions/*` | `conditions-ui.js` | attrs on HTML | inspector | **Pilot #2** |
| **History** | `SitePageRevision*`, revisions controller | `revisions-ui.js` | `voodbuilder_site_page_revisions` | page editor UI | **Pilot #1** |
| **Templates** | `PageTemplate`, page-templates controllers, starters | `page-templates-sidebar.js` | `voodbuilder_page_templates` | editor sidebar | Phase 6 #3 |
| **Components** | `BuilderComponent`, components controllers/support | `components-ui.js` (~72KB) | `voodbuilder_components`, `voodbuilder_global_classes` | editor library | Agency boundary |
| **Popups** | `BuilderPopup*`, popup controllers, Filament resource | `popups-ui.js`, `popups-runtime.js` | `voodbuilder_popups`, `voodbuilder_popup_events` | `PopupResource` | **Last commercial extract** |
| **Licensing** | `Support/License/VoodbuilderLicense` (key-only) | — | cache | — | Phase 7–10 |
| **Marketplace** | none | none | — | — | Phase 11 |

## Coupling that blocks clean extraction

1. **`VoodbuilderServiceProvider`** registers routes, blocks, bindings, Livewire, Filament assets, and rich-content blocks in one boot path.
2. **`VoodbuilderPlugin`** hard-registers `PopupResource` behind `config('voodbuilder.popups.enabled')` — feature flag, not module registry.
3. **`editor/init.js`** imports page, chrome, popup, bindings, components, templates, conditions, revisions in one bootstrap.
4. **Public render path** (`EditorRenderer` and related) assumes bindings/conditions/components helpers may always be present.
5. Optional **vtuts** wiring exists via `ConfigureVtutsForVoodbuilder` — treat as host/integration adapter only; do not deepen package coupling.

## Proposed sequence of small, testable PRs

### PR-A — Audit + baseline (this deliverable)

- Docs only under `docs/audit/`, `docs/baseline/`, `docs/progress/`.
- Frontend CI job for Vitest smoke.
- **No** PHP/JS behaviour change.

### PR-B — Characterisation tests (Phase 2)

- Fix or quarantine the **16 baseline PHPUnit failures** with truthful expectations or isolated env fixes (no product rewrite).
- Add Feature tests for: route resolution, publication schedule, theme clone/assign, menus, translations, bindings, conditions, templates, popups, save/load.
- Seed `tests/Fixtures/0.0.11/` from baseline samples.

### PR-C — Public contracts (Phase 3)

- Introduce `VoodBuilderModule`, contributor interfaces, DTOs under `src/Contracts/`.
- Adapters wrap existing classes; no file moves yet.

### PR-D — Module registry + History pilot (Phase 4)

- `ModuleRegistry` + register `HistoryModule`.
- History routes/UI still call same classes via module `register()`.
- Test: disable History module → revision endpoints absent; Core still boots.

### PR-E — Conditions pilot (Phase 4 cont.)

- Same pattern as History.
- Test: disable Conditions → public pages still render (conditions ignored safely).

### PR-F — Editor registries + compatibility bridge (Phase 5)

- JS: command/block/panel registries; keep `editor/init.js` as thin orchestrator.
- Compatibility bridge marked `@deprecated remove-by 0.2.0`.

### PR-G… — Progressive PHP module folders (Phase 6)

Order locked by master doc: History → Conditions → Templates → Themes → Menus → Layouts → Pages → Dynamic Data → Components → Popups.

Each PR: move classes under `src/Modules/<Name>/`, update autoload/namespaces, keep public API facades.

### PR-H — Entitlements (Phase 7)

- `VoodBuilder::can('…')` + test capability provider.
- No AnyStack yet.

### PR-I — Commercial boundaries (Phase 8)

- Soft-boundary Dynamic Data advanced + Components behind capability gates and optional module enablement.
- Community boot path must not require proprietary packages (still same repo).

### PR-J — Popup package boundary prep (Phase 9 prep inside repo)

- Popups as internal module with zero Core hard imports.
- Public render must no-op safely if module disabled; admin warning for orphaned data.

### PR-K — AnyStack adapter (Phase 10)

- Licensing adapter interface; cache; expiry never breaks public render.

### PR-L — SDK docs + sample plugin (Phase 11)

- Document registration APIs; sample third-party block/data-source/condition **inside tests or docs sample**, not sibling product plugins.

## Acceptance per phase

Copied from master plan §20; progress logged in `docs/progress/<phase>.md`.

## Explicit non-goals until later PRs

- Splitting Composer packages physically (except prep for popups).
- Editor version upgrade.
- UI redesign / rename of user-facing concepts.
- DB column renames.
- Fixing “unused” code without proof.
- Hard dependencies on other Cosmolab plugins.
