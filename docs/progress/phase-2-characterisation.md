# Phase 2 progress — characterisation (partial)

## Work completed

- Diagnosed freeze failures (16 PHPUnit + 1 Vitest).
- Restored **green** suite: `561` PHPUnit assertions path + `23` Vitest.
- Tailwind compile self-contained for package/Testbench (`resolveAppRoot`, optional typography plugin, package npm deps).
- Fixed chrome `resolveSettings` to keep active settings root when selection is ambiguous.
- Fixed home locale redirect using `SiteLocales` (site default ≠ runtime `app.locale`).
- Updated stale footer renderer characterisation assertion.

## Files changed

- `src/Support/SiteLocales.php` (new)
- `src/Http/Controllers/HomeController.php`
- `src/Models/SitePage.php`
- `src/Support/GrapesJs/GrapesJsComponentTailwindCompiler.php`
- `scripts/compile-component-tailwind.mjs`
- `resources/js/grapesjs/blocks/settings/registry.js`
- `package.json` / `package-lock.json` (Tailwind compile toolchain)
- tests: `SitePageTranslationTest`, `GrapesJsServerBlockRendererTest`

## Tests executed

```text
vendor/bin/phpunit → OK (561 tests, 1737 assertions)
npm run test:js → OK (23 tests)
```

## Remaining Phase 2 work

- Add Feature coverage still thin: scheduled publication, theme clone/assign E2E, conditions E2E, template/component import, layout save/load.
- Seed `tests/Fixtures/0.0.11/` from baseline + starter templates.
- Capture screenshots under `docs/baseline/screenshots/` when demo available.

## Rollback

Revert this commit; suite returns to 16 failures / missing Tailwind package deps.
