# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- `MenuRouteCatalog` for Filament navigation menus: searchable select of public GET routes with automatic `route_match` patterns
- Configurable `menus.route_exclude_patterns` to hide admin and internal routes from the menu builder

## [0.0.7] - 2026-06-08

### Fixed

- Load `filament-cookie-consent` translations when the package is excluded from Laravel auto-discovery (Filament settings labels no longer show raw translation keys)

## [0.0.6] - 2026-06-08

### Fixed

- `vpress:install` always runs with the English locale so Artisan output and default seeded content stay in English regardless of the host OS or app locale
- Homepage `latest_vtuts` block uses `vtuts::vtut-card`

## [0.0.5] - 2026-06-08

### Fixed

- `SitePage::getUrl()` no longer throws when the `home` route is missing (uses `VpressUrls::home()`)
- `vpress:install` removes Laravel’s default welcome route so vpress owns `/` and the public theme loads
- vtuts locale fallback URL uses `VpressUrls::home()` instead of a hardcoded `route('home')`

## [0.0.4] - 2026-06-08

### Fixed

- `vpress:install` post-install instructions are in English again
- Theme CSS path resolves automatically for Composer installs (`vendor/...`) and monorepo path repos (`packages/...`)
- `vpress:install` patches `vite.config.js` when the theme entry is missing or still uses the legacy `packages/` path
- Doc outline scroll-spy handles duplicate heading links and bottom-of-page active state
- Default home CTA uses the configured vtuts URL prefix instead of a hardcoded `/vtuts` path

### Changed

- `config/vpress.php` uses `VpressPaths::defaultViteEntries()` so `@vite` always matches the install location

## [0.0.2] - 2026-06-06

### Added

- Primary site language setting in Filament (no locale prefix for the primary language)
- Optional mobile logo upload in site settings
- Profile dropdown menu with language, theme, and account actions
- English and Italian strings for nav and settings helpers

### Changed

- Mobile navigation drawer with in-panel close control and improved scroll lock
- Sticky right-hand doc aside aligned with VitePress behaviour
- Account, register, and account settings pages migrated to Tailwind (`vp-*` tokens)
- Language switcher section hidden when the switcher is disabled in settings
- Search modal no longer locks page scroll; logo sizing tuned for desktop and mobile
- Home routes skip legacy `/{locale}` prefixes when tutorials use slug-based locales

### Removed

- Legacy “More” overflow menu in the header (replaced by profile menu)

[0.0.5]: https://github.com/voodflow/vpress/releases/tag/0.0.5

[0.0.4]: https://github.com/voodflow/vpress/releases/tag/0.0.4

[0.0.2]: https://github.com/voodflow/vpress/releases/tag/0.0.2

## [0.0.1] - 2026-06-04

### Added

- Initial public release
- VitePress-inspired theme with mobile hamburger navigation
- Filament site pages, navigation menus, and settings
- Light/dark theme with admin defaults and optional user toggle
- SEO, favicon, logo, and social sharing defaults
- Cookie consent integration
- Account page with avatar support
- Comment notification bell (frontend + Filament)
- Extensible RichEditor custom blocks

[0.0.1]: https://github.com/voodflow/vpress/releases/tag/0.0.1
