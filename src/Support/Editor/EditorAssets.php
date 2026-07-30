<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Vite;
use Voodflow\Voodbuilder\Support\ConfigureNpmForVoodbuilder;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

/**
 * Editor Assets.
 */
final class EditorAssets
{
    public static function editorScriptEntry(): string
    {
        return (string) config('voodbuilder.editor.vite', VoodbuilderPaths::editorViteEntry());
    }

    public static function editorStyleEntry(): string
    {
        return VoodbuilderPaths::editorCssEntry();
    }

    public static function blockPreviewStyleEntry(): string
    {
        return VoodbuilderPaths::editorBlockPreviewCssEntry();
    }

    /**
     * @return list<string>
     */
    public static function viteEntries(): array
    {
        $entries = [
            self::editorScriptEntry(),
            self::editorStyleEntry(),
        ];

        if (VoodbuilderSectionEditorBlocks::isAvailable()) {
            $entries[] = self::blockPreviewStyleEntry();
        }

        return $entries;
    }

    /**
     * Vite entries for visual editor pages (layout, page, popup).
     * Excludes site runtime bundles that are unused while editing.
     *
     * @return list<string>
     */
    public static function editorPageViteEntries(bool $chromeLayoutEditor = false, ?string $subTheme = null): array
    {
        $entries = [
            self::editorScriptEntry(),
            self::editorStyleEntry(),
        ];

        if (! $chromeLayoutEditor && VoodbuilderSectionEditorBlocks::isAvailable()) {
            $entries[] = self::blockPreviewStyleEntry();
        }

        return self::withOptionalPublicCss(
            array_merge([VoodbuilderPaths::themeCssRelativePath()], $entries),
            $subTheme,
            includeTabsAndForms: false,
        );
    }

    /**
     * Public page / editor host Vite entries.
     *
     * @return list<string>
     */
    public static function pageViteEntries(
        bool $editorEditor = false,
        bool $chromeLayoutEditor = false,
        ?string $subTheme = null,
    ): array {
        if ($editorEditor) {
            return self::editorPageViteEntries($chromeLayoutEditor, $subTheme);
        }

        $configured = config('voodbuilder.assets.vite');

        if (is_array($configured) && $configured !== []) {
            return self::withOptionalPublicCss(array_values($configured), $subTheme);
        }

        return self::withOptionalPublicCss(VoodbuilderPaths::defaultViteEntries(), $subTheme);
    }

    /**
     * Active sub-theme CSS as a Vite entry (null when already bundled in theme.css = site).
     */
    public static function subThemeCssViteEntry(?string $subTheme): ?string
    {
        $id = SubThemeResolver::normalize($subTheme ?? SubThemeResolver::siteDefault());

        if (in_array($id, [SubThemeResolver::SITE, 'events'], true)) {
            return null;
        }

        $absolute = self::resolveSubThemeCssAbsolutePath($id);

        if ($absolute === null || ! is_file($absolute)) {
            return null;
        }

        return VoodbuilderPaths::relativeToBasePath($absolute);
    }

    /**
     * Known optional CSS inputs that should be listed in vite.config.js.
     *
     * @return list<string>
     */
    public static function optionalPublicCssViteInputs(): array
    {
        $entries = [
            VoodbuilderPaths::editorTabsCssEntry(),
            VoodbuilderPaths::editorFormsCssEntry(),
        ];

        foreach (app(SubThemeRegistry::class)->ids() as $id) {
            if (in_array($id, [SubThemeResolver::SITE, 'events'], true)) {
                continue;
            }

            $entry = self::subThemeCssViteEntry($id);

            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return array_values(array_unique($entries));
    }

    public static function isBuilt(): bool
    {
        if (class_exists(Vite::class) && Vite::isRunningHot()) {
            return true;
        }

        foreach (self::viteEntries() as $entry) {
            if (! self::hasBuiltAsset($entry)) {
                return false;
            }
        }

        return true;
    }

    public static function buildInstructions(): string
    {
        $missing = ConfigureNpmForVoodbuilder::missingFromPackageJson();

        if ($missing !== []) {
            return 'Missing npm packages: '.implode(', ', $missing).'. '
                .'Run `php artisan voodbuilder:install --skip-migrate --skip-seed --with-npm-build` '
                .'(or `php artisan voodbuilder:sync-npm-deps --install`, then `npm run build`).';
        }

        $script = self::editorScriptEntry();
        $style = self::editorStyleEntry();

        return "Add `{$script}` and `{$style}` to vite.config.js input, then run `npm install --legacy-peer-deps && npm run build`. "
            .'Or run `php artisan voodbuilder:install --skip-migrate --skip-seed --with-npm-build`.';
    }

    /**
     * @param  list<string>  $entries
     * @return list<string>
     */
    protected static function withOptionalPublicCss(
        array $entries,
        ?string $subTheme,
        bool $includeTabsAndForms = true,
    ): array {
        if ($includeTabsAndForms) {
            foreach ([
                VoodbuilderPaths::editorTabsCssEntry(),
                VoodbuilderPaths::editorFormsCssEntry(),
            ] as $optionalCss) {
                if (self::canResolveViteEntry($optionalCss)) {
                    $entries[] = $optionalCss;
                }
            }
        }

        $subThemeEntry = self::subThemeCssViteEntry($subTheme);

        if ($subThemeEntry !== null && self::canResolveViteEntry($subThemeEntry)) {
            $entries[] = $subThemeEntry;
        }

        return array_values(array_unique(array_filter(
            $entries,
            static fn (mixed $entry): bool => is_string($entry) && $entry !== '',
        )));
    }

    /**
     * Optional public CSS must exist in the Vite manifest (or Vite hot) so @vite does not throw.
     * When no manifest exists yet, keep declaring the entry (same failure mode as required assets).
     */
    protected static function canResolveViteEntry(string $entry): bool
    {
        if (class_exists(Vite::class) && Vite::isRunningHot()) {
            return true;
        }

        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            return true;
        }

        return self::hasBuiltAsset($entry);
    }

    protected static function resolveSubThemeCssAbsolutePath(string $subThemeId): ?string
    {
        $cssPath = app(SubThemeRegistry::class)->cssPath($subThemeId);

        if (! is_string($cssPath) || $cssPath === '') {
            return null;
        }

        if (str_starts_with($cssPath, 'themes/')) {
            $absolute = VoodbuilderPaths::packagePath().'/resources/'.$cssPath;

            return is_file($absolute) ? $absolute : null;
        }

        if (str_starts_with($cssPath, 'resources/')) {
            $absolute = base_path($cssPath);

            return is_file($absolute) ? $absolute : null;
        }

        $absolute = base_path($cssPath);

        return is_file($absolute) ? $absolute : null;
    }

    protected static function hasBuiltAsset(string $entry): bool
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            return false;
        }

        $decoded = json_decode((string) file_get_contents($manifest), true);

        return is_array($decoded) && array_key_exists($entry, $decoded);
    }
}
