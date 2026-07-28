# Phase 10 — AnyStack adapter

## Delivered

- `LicenceClient` + `LicenceClientException` contracts
- `AnyStackLicenceClient` (HTTP, isolated)
- `AnyStackEntitlementProvider` with forever snapshot + grace window
- `EntitlementProviderFactory` (`config` | `anystack` | `testing`)
- Config: `voodbuilder.license.driver`, `license.anystack.*`

## Acceptance

Temporary remote outage:

1. serves last snapshot within `grace_seconds` (default 7 days)
2. after grace → Community soft fallback
3. never throws to public render callers

## Tests

`tests/Licensing/AnyStackEntitlementProviderTest.php`
