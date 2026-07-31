---
title: Installation (host app)
description: Wire VoodBuilder into a Laravel + Filament host.
---

# Installation (host app)

Exact Composer steps depend on your monorepo or Satis feed. Conceptually:

1. Require `voodflow/voodbuilder`  
2. Publish config: `config/voodbuilder.php` (and optionally `voodbuilder-integrations.php`)  
3. Run migrations / `php artisan voodbuilder:install`  
4. Register `VoodbuilderPlugin::make()` on the Filament panel  
5. Install npm peer deps (GrapesJS + optional plugins) and ensure Vite entries for the editor  
6. `npm run build`

`voodbuilder:install` attempts to patch `vite.config.js` with editor entries when possible.

## Policies

Authorize SitePage / ChromeLayout / menu models with host policies. Editor routes are authenticated and throttled.

## Assets

Theme CSS, section utilities, and font publish helpers must be part of the host Vite build. See package `docs/BUILD.md` and `docs/FONTS.md` for engineering detail.

## Media library

`voodbuilder:install` publishes Spatie Media Library config and the `media` table migration.

Reusable images/videos live on the singleton `MediaLibrary` model (collections `images` / `videos`), managed in Filament → **Media library** via `SpatieMediaLibraryFileUpload`.

The visual editor Asset Manager lists and uploads into the same collections (`POST /voodbuilder/editor/upload`, `GET /voodbuilder/editor/media`). The in-canvas Jodit image editor keeps using that upload endpoint.
