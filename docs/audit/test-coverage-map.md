# Test coverage map

## Inventory

| Suite | Count (approx.) | Role |
|---|---|---|
| `tests/Unit/*` | ~119 files | Support/renderer/normalizer coverage |
| `tests/Feature/*` | 14 files | HTTP save, popups, translations, security |
| `tests/js/modular-refactor.test.js` | 1 | Vitest smoke for core/settings helpers (**1 failing** at freeze: `resolveSettings uses active settings root when selection is ambiguous`) |

## Behaviours well protected

- Many Editor normalizers / chrome layout helpers / theme palette / menu placements
- Some popup analytics & public popup endpoints
- Page translation & security feature tests (partially failing at freeze)
- Component/page template bundle unit tests (partially failing)

## Critical behaviours with weak or failing coverage (Phase 2 priority)

| Flow | Status at freeze |
|---|---|
| Page editor save (`EditorPageSaveTest`) | **FAILING** |
| Popup content save | **FAILING** |
| Locale home redirect | **FAILING** |
| Pasted component / page CSS compile path | **FAILING** (many cases) |
| Server block footer render | **FAILING** |
| Corrupted page CSS repair | **FAILING** |
| Scheduled publication | needs explicit Feature tests |
| Theme assign/clone | unit coverage exists; add Feature |
| Conditions end-to-end | mostly unit |
| Template import URL | controller Feature exists; expand |
| Editor load/save JSON roundtrip | incomplete |
| Layout save/load | incomplete |

## Missing suite folders (to add later)

```text
tests/Architecture/
tests/Contracts/
tests/Modules/
tests/Licensing/
tests/Compatibility/
tests/Upgrade/
tests/Fixtures/0.0.11/
```

## Baseline PHPUnit result (Phase 0)

`561` tests, `1711` assertions, **`16` failures**, `3` skipped.

Failures (names):

1. `EditorPageSaveTest::test_admin_can_save_editor_payload`
2. `PopupsPublicControllerTest::test_authenticated_builder_user_can_save_popup_content`
3. `SitePageTranslationTest::test_missing_home_for_locale_redirects_to_default_locale_home`
4–13. `EditorPastedComponentNormalizerTest` (CSS compile / dark scope / recompile cases)
14. `EditorPhaseOneRenderersTest::test_page_save_sync_compiles_instance_html_without_theme_html_migration`
15. `EditorServerBlockRendererTest::test_renders_server_block_html`
16. `SitePageEditorTest::test_editor_renderer_css_repairs_corrupted_page_styles_in_package`
