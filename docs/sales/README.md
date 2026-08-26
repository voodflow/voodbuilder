# VoodBuilder — sales overview

**Visual site builder for FilamentPHP** — pages, chrome, menus, themes, and a GrapesJS-based editor with a first-class companion SDK.

## Who it is for

- Product teams shipping a branded public site inside a Laravel/Filament host
- Agencies that need a maintainable page builder without forking vendor code
- ISVs building commercial **companion** packs (blocks, dynamics, templates, popups, forms)

## Core value

| Capability | Outcome |
|------------|---------|
| Site pages + chrome | Publishable pages with header/footer layouts |
| Visual editor | `?edit=1` canvas with sections, style, conditions, revisions |
| Themes & fonts | Sub-themes, webfont catalog, CSS compile pipeline |
| Extension SDK | Blocks, bindings, conditions, modules, entitlements |
| Soft companions | Upsell UX when paid packs are missing — core never fatals |

## Core vs companions (commercial framing)

| Layer | Ships in core? | Examples |
|-------|----------------|----------|
| Editor shell + registries | Yes | Canvas, block registry, condition hooks |
| Marketing section library | Yes (baseline) | Bundled section JSON |
| Components / Dynamics / Templates Pro | Companion | Soft-gated panels |
| Forms / Cookie / Analytics / Popups | Companion | Register modules + blocks |
| Elements pack | Companion | Premium BlockManager sections |

Message: **buy core for the site; add companions for specialized authoring**.

## Technical buyer proof points

- Public facade `Voodbuilder` — registration-only extension model
- GrapesJS stays **vanilla**; custom code lives in `Support/Editor` and `resources/js/editor`
- Entitlements (`can` / `cannot`) for edition-aware UI without hardcoding SKU names in companions
- Works alongside Vforms (form blocks) and Voodflow (optional automation) without hard requires

## Next reads

- [Developer hub](../developer/README.md)
- [Companion integration](../developer/companion-integration.md)
- [User companions overview](../manual/user/companions-overview.md)
- [Manuals index](../manual/index.md)
