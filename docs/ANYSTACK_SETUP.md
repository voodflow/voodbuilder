# AnyStack + license dashboard API

VoodBuilder Core talks to an AnyStack-style entitlements endpoint when
`VOODBUILDER_LICENSE_DRIVER=anystack`. Community / Packagist installs keep
working with `driver=config` (no remote key required).

## Env

```env
VOODBUILDER_LICENSE_DRIVER=anystack
VOODBUILDER_LICENSE_KEY=vb_live_...
VOODBUILDER_ANYSTACK_ENDPOINT=https://license.example.com
VOODBUILDER_ANYSTACK_TIMEOUT=5
VOODBUILDER_ANYSTACK_GRACE_SECONDS=604800
```

Until AnyStack returns per-licence catalog credentials, keep the shared CDN
secret in env (server-side only):

```env
VOODBUILDER_CATALOG_TOKEN=...
# or VOODBUILDER_ELEMENTS_CATALOG_TOKEN / page_templates.catalog_token
```

## Entitlements request

`POST {endpoint}/entitlements`

```json
{
  "licence_key": "vb_live_...",
  "product": "voodbuilder"
}
```

Expected response (extra fields ignored):

```json
{
  "edition": "developer",
  "active": true,
  "capabilities": ["editor.core", "blocks.official.complete", "..."],
  "identifier": "seat-1",
  "expires_at": "2027-01-01T00:00:00Z",
  "message": null,
  "catalog_credentials": {
    "elements": "short-lived-or-per-licence-token",
    "page_templates": "optional-templates-token"
  }
}
```

`edition` values: `community` | `developer` | `professional` (alias of developer) | `agency`.  
Core normalizes `developer` → runtime token `professional`. Omit `capabilities` to use `EditionCapabilityMatrix::forEdition()`.

`active: false` (non-renewal): authoring falls to Community; **published pages keep rendering**.  
Outage: fail-open on the last successful snapshot (never strip paid authoring because the license API is down).

`catalog_credentials` is optional. When present, Core prefers it over `.env`
for CDN `X-VoodBuilder-Catalog-Token` (Elements + page templates).

## AnyStack products to create

Listino: [PRICING.md](./PRICING.md).

| AnyStack product | Price | Grants `edition` | Private Composer packages |
|---|---|---|---|
| VoodBuilder Community | 0 € | `community` | — (Packagist) |
| VoodBuilder Developer | 149 €/yr · 1 seat | `developer` or `professional` | elements, dynamic-data, templates |
| VoodBuilder Agency | 399 €/yr · ≤5 seats | `agency` | + components, dynamic-api, **vpopups** |
| VoodPopups (standalone) | 79 €/yr | own product key | `vpopups` |
| VoodForms | 199 €/yr | own product key | `vforms` |

Entitlement endpoint product code for Core remains `"product": "voodbuilder"`.

## Admin API (future Filament licenses plugin)

Auth: `web` + `auth` + page-builder access (same gate as the editor).

| Method | Path | Name |
|--------|------|------|
| `GET` | `/voodbuilder/admin/license/status` | `voodbuilder.admin.license.status` |
| `POST` | `/voodbuilder/admin/license/refresh` | `voodbuilder.admin.license.refresh` |

`schema_version: 1` payload includes:

- `licence` — masked key, edition, expiry, messages
- `capabilities` — granted list
- `products` — install/capability flags for core, elements, components, templates, dynamic_data, popups, vdocs, vtuts, vmedia, vcookiebar
- `catalog` — URL/token readiness + `credential_source` (`anystack` \| `env`)
- `cache_grace` — snapshot age / grace mode
- `distribution` — commercial channel map (Packagist vs AnyStack)

Refresh forgets the AnyStack snapshot + local entitlement cache, then re-resolves.

## Distribution map (product plan)

| Package | Channel | Notes |
|---------|---------|--------|
| `vmedia` | Packagist | Released |
| `voodbuilder` | Packagist | Community |
| `voodbuilder-elements` | AnyStack | Developer + Agency |
| `voodbuilder-dynamic-data` | AnyStack | Developer + Agency |
| `voodbuilder-templates` | AnyStack | Developer + Agency |
| `voodbuilder-components` | AnyStack | Agency |
| `voodbuilder-dynamic-api` | AnyStack | Agency |
| `vpopups` | AnyStack | 79 € SKU or included in Agency |
| `vdocs` / `vtuts` | AnyStack | Own licence |
| `vcookiebar` | Packagist | When ready |

vdocs / vtuts / vmedia / vcookiebar are reported as installed companions but are
**not** licensed by the VoodBuilder Core key (`licensed_by_voodbuilder: false`).
