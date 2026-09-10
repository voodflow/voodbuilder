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
  "edition": "professional",
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

`catalog_credentials` is optional. When present, Core prefers it over `.env`
for CDN `X-VoodBuilder-Catalog-Token` (Elements + page templates).

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
| `voodbuilder-elements` | AnyStack | Catalog CDN |
| `voodbuilder-components` | AnyStack | Agency |
| `voodbuilder-templates` | AnyStack | Pro |
| `vpopups` | AnyStack | Separate SKU |
| `vdocs` / `vtuts` | AnyStack | Own licence |
| `vcookiebar` | Packagist | When ready |

vdocs / vtuts / vmedia / vcookiebar are reported as installed companions but are
**not** licensed by the VoodBuilder Core key (`licensed_by_voodbuilder: false`).
