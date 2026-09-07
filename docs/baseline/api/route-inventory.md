# Baseline API route inventory (0.0.11 line)

Captured from `VoodbuilderServiceProvider` + `routes/web.php` during Phase 0 audit.
Full behavioural response bodies deferred until Feature tests are green.

## Public

- GET `/` → `home`
- GET `/{locale}` → `home.localized`
- GET pages show → `voodbuilder.pages.show`
- GET search → `voodbuilder.search`
- auth + account routes

## Editor (auth)

- blocks, bindings, link-targets, media, upload
- pages update + revisions
- components + global-classes CRUD
- page-templates CRUD/import/export/catalog
- popups CRUD + content update
- chrome-layouts content update
- editors: popups/{id}/editor, chrome-layouts/{id}/editor

## Public popups

- GET popups/data
- POST popups/events
