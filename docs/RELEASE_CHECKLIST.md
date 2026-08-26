# VoodBuilder release checklist

Use before tagging a release of `voodflow/voodbuilder`.

## Missing for release (must)

- [ ] Version bump + changelog
- [ ] `php artisan voodbuilder:install` on a clean host (migrations, npm sync, Vite entries)
- [ ] Config publish path documented; `config/voodbuilder.php` defaults sane
- [ ] GrapesJS version pinned (no `*`); smoke `?edit=1` after any bump
- [ ] Soft-gate smoke: boot without Components / Dynamics / Templates / Forms companions
- [ ] Public page render + chrome + menus on a sample site
- [ ] English docs index (`docs/README.md`) links sales, developer hub, manuals
- [ ] No patches under `node_modules/grapesjs` in the release tree

## Nice-to-haves

- [ ] Screenshot pack for user manual placeholders
- [ ] VitePress sidebar merge into vdocs verified
- [ ] Performance pass on homepage / section library load
- [ ] Entitlement matrix reviewed against commercial SKUs

## Test status

**Result (2026-08-25, Docker PHP 8.4 / package phpunit|pest):** PASS (2 skipped)

624 tests, 1950 assertions. Fixed nav sort expectation and templates monorepo commercial-boundary assertions.

## Code quality

- [ ] Pint / project formatter clean on dirty PHP
- [ ] No debug leftovers in `src/` or `resources/js/editor/`
- [ ] Public facade methods documented in `docs/manual/developer/`
- [ ] Companion handoff docs (`docs/handoff/`) not required for core tag but linked if relevant

## Security review

| Area | Notes |
|------|-------|
| Authz | Editor routes authenticated; policies on pages/menus/chrome |
| XSS | Escape server block output; sanitize rich content |
| Uploads / media | Follow core media guards |
| Entitlements | No client-only gates for paid features |
| Supply chain | Pin GrapesJS; do not ship modified `node_modules` |

## Sign-off

| Role | Name | Date |
|------|------|------|
| Maintainer | | |
| Security review | | |
| Docs review | | |
