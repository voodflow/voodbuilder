# Phase 8 — Commercial package boundaries (soft)

## Soft boundaries (same repo)

- **Components** (Agency): module registers only when `Voodbuilder::can('components.library')`
- **Templates Pro/Agency**: import / remote-install / export routes gated by capability; local CRUD stays Community
- **Dynamic Data**: module stays for Community single-record; editor exposes `dynamicDataCollections` only with `dynamic-data.collections`
- **Popups**: module requires `popups.builder` capability
- Filament `ModelIntegrationResource` / `PopupResource` follow the same capability checks

## Community acceptance

`tests/Licensing/CommercialBoundaryTest.php` boots with `license.edition=community` and asserts:

- Components routes absent
- Core Templates / Dynamic Data / Popups present
- Pro template import/export routes absent

## Still same package

No proprietary Composer package yet — soft entitlement gates only. Physical splits remain Phase 9+ for Popups and later Pro packages.
