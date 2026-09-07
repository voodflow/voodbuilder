# Risk register

| ID | Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| R1 | Editor init order breaks when splitting JS | Editor unusable | High | Characterisation Vitest + bridge; split listeners last |
| R2 | Public HTML pipeline assumes optional modules | Blank/500 pages | High | Null-object renderers; tests with modules off |
| R3 | 16 failing PHPUnit tests hide regressions | False confidence | High | Phase 2: green suite before structural moves |
| R4 | Popup extract leaves Core imports | Broken Community | Medium | Boundary audit + architecture tests |
| R5 | Changing stored JSON/HTML attrs | Data loss | Medium | No format change; versioned migrators only |
| R6 | Licence checks scattered later | Unmaintainable | Medium | Capability API only (plan §4.5) |
| R7 | Editor upgrade during refactor | Compound failures | Medium | Forbidden until modularisation done |
| R8 | Sibling plugin hard deps (vtuts) | Package coupling | Medium | class_exists/config only; no new requires |
| R9 | Duplicate `canvas:frame:load` listeners | Perf/leaks | Medium | Event registry; assert once |
| R10 | Tailwind compile env differs CI vs local | Flaky CSS tests | High (seen) | Pin toolchain; skip-with-reason only if proven env gap |
| R11 | Filament/core upgrades mid-refactor | Noise | Medium | No dependency upgrades without approval |
| R12 | Removing “unused” chrome/footer paths | Render regressions | Medium | Prove unused via fixtures first |

## Audit questions — short answers

| Question | Answer |
|---|---|
| Which classes directly instantiate other feature classes? | ServiceProvider + renderers/gates; many concrete `Support/Editor/*` calls |
| Which service providers register everything? | `VoodbuilderServiceProvider` (+ Filament `VoodbuilderPlugin`) |
| Which JS features depend on init order? | Chrome shell/layout before bindings/dynamic refresh; frame:load handlers |
| Which state is stored globally? | Mostly `editor.__voodbuilder*`; few `window.__vb*` |
| Which events are listened more than once? | `load`, `canvas:frame:load`, `component:add` |
| Which utilities are duplicated? | Legacy shims (`block-settings` vs `blocks/settings`, chrome editor-* vs `chrome/`) |
| Which routes belong to which module? | See `route-map.md` |
| Which tables belong to which module? | See `database-map.md` |
| Which migrations stay Core? | Pages, menus, settings, chrome, revisions, templates (local); popups extractable |
| Which features can disappear without breaking saved JSON? | Popups UI (content remains); new premium packs; not bindings attrs already stored |
| Which editor features shared by page/layout/popup? | Core grapes bootstrap, style inspector, layers subset, tailwind rebuild |
| Which APIs already public? | See `public-api-candidates.md` |
| Assumes Popups/Components/Dynamic Data? | See `plugin-boundary-candidates.md` |
| Which tests protect behaviour / gaps? | See `test-coverage-map.md` |
