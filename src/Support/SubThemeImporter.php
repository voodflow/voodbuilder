<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Voodflow\Vpress\Models\VpressSettings;
use ZipArchive;

final class SubThemeImporter
{
    public static function resolveArchiveUploadPath(mixed $uploaded, string $disk = 'local'): ?string
    {
        if ($uploaded instanceof TemporaryUploadedFile) {
            $path = $uploaded->getRealPath();

            return is_string($path) && $path !== '' && is_file($path) ? $path : null;
        }

        if (is_array($uploaded)) {
            return self::resolveArchiveUploadPath($uploaded[0] ?? null, $disk);
        }

        if (! is_string($uploaded) || $uploaded === '') {
            return null;
        }

        $storage = Storage::disk($disk);

        if ($storage->exists($uploaded)) {
            return $storage->path($uploaded);
        }

        $legacyPath = storage_path('app/'.$uploaded);

        return is_file($legacyPath) ? $legacyPath : null;
    }

    public static function themeExists(string $id): bool
    {
        return is_file(ThemeConvention::appCssPath($id))
            || app(SubThemeRegistry::class)->exists($id);
    }

    public static function suggestAvailableId(string $baseId): string
    {
        $baseId = Str::kebab($baseId);

        if ($baseId === '' || $baseId === 'default') {
            $baseId = 'theme-copy';
        }

        if (! self::themeExists($baseId)) {
            return $baseId;
        }

        $suffixes = ['-copy'];

        for ($index = 2; $index <= 99; $index++) {
            $suffixes[] = '-'.$index;
        }

        foreach ($suffixes as $suffix) {
            $candidate = $baseId.$suffix;

            if (! self::themeExists($candidate)) {
                return $candidate;
            }
        }

        return $baseId.'-'.Str::lower(Str::random(6));
    }

    public static function import(
        string $archivePath,
        bool $force = false,
        bool $importColors = true,
        ?string $targetId = null,
        bool $renameOnConflict = true,
        ?string $label = null,
    ): SubThemeImportResult {
        if (! is_file($archivePath)) {
            return new SubThemeImportResult(false, $targetId ?? '', "Archive not found: {$archivePath}");
        }

        $temporaryDirectory = storage_path('app/vpress-theme-imports/'.uniqid('import-', true));

        try {
            self::extractArchive($archivePath, $temporaryDirectory);

            $manifest = self::readManifest($temporaryDirectory);
            $id = self::resolveTargetId($manifest, $targetId);
            $renamedFrom = null;

            if ($id === '' || $id === 'default' || ! preg_match('/^[a-z][a-z0-9-]*$/', $id)) {
                return new SubThemeImportResult(false, $id, 'Invalid theme id in archive.');
            }

            $themeExists = self::themeExists($id);

            if ($themeExists && $force) {
                $renamedFrom = null;
            } elseif ($themeExists && $renameOnConflict) {
                $renamedFrom = $id;
                $id = self::suggestAvailableId($id);
            } elseif ($themeExists) {
                return new SubThemeImportResult(
                    false,
                    $id,
                    "Theme \"{$id}\" already exists. Re-run with --force to overwrite.",
                );
            }

            self::writeThemeFiles(
                sourceDirectory: $temporaryDirectory,
                id: $id,
                rewriteFromId: self::resolveRewriteFromId($manifest, $id),
            );

            $definition = self::normalizeDefinition($id, $manifest['definition'] ?? [], $label, $renamedFrom);
            $appCssPath = ThemeConvention::appCssPath($id);
            $configRegistered = ConfigureSubThemesForVpress::upsertInConfig($id, $definition);
            $importAppended = AppendThemeStylesheetImport::append($appCssPath);
            SyncThemeStylesheetImports::sync();

            app(SubThemeRegistry::class)->register($id, $definition);

            $colorsImported = false;

            if ($importColors) {
                $colorsImported = self::importAppearanceColors($id, $manifest['appearance']['colors'] ?? null);
            }

            return new SubThemeImportResult(
                success: true,
                id: $id,
                configRegistered: $configRegistered,
                importAppended: $importAppended,
                colorsImported: $colorsImported,
                cssPath: $appCssPath,
                renamedFrom: $renamedFrom,
            );
        } catch (\Throwable $exception) {
            return new SubThemeImportResult(false, $targetId ?? '', $exception->getMessage());
        } finally {
            if (is_dir($temporaryDirectory)) {
                File::deleteDirectory($temporaryDirectory);
            }
        }
    }

    protected static function extractArchive(string $archivePath, string $destinationDirectory): void
    {
        File::ensureDirectoryExists($destinationDirectory);

        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new \InvalidArgumentException("Unable to open archive: {$archivePath}");
        }

        $zip->extractTo($destinationDirectory);
        $zip->close();
    }

    /**
     * @return array<string, mixed>
     */
    protected static function readManifest(string $directory): array
    {
        $manifestPath = $directory.'/'.SubThemeExporter::MANIFEST_FILE;

        if (! is_file($manifestPath)) {
            throw new \InvalidArgumentException('Archive is missing manifest.json.');
        }

        $data = json_decode(File::get($manifestPath), true);

        if (! is_array($data)) {
            throw new \InvalidArgumentException('Invalid manifest.json.');
        }

        if (($data['format'] ?? null) !== SubThemeExporter::FORMAT) {
            throw new \InvalidArgumentException('Unsupported theme archive format.');
        }

        if ((int) ($data['version'] ?? 0) !== SubThemeExporter::VERSION) {
            throw new \InvalidArgumentException('Unsupported theme archive version.');
        }

        if (! is_string($data['id'] ?? null) || blank($data['id'])) {
            throw new \InvalidArgumentException('Theme archive is missing an id.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected static function resolveTargetId(array $manifest, ?string $targetId): string
    {
        if (filled($targetId)) {
            return Str::kebab($targetId);
        }

        return Str::kebab((string) $manifest['id']);
    }

    protected static function writeThemeFiles(string $sourceDirectory, string $id, ?string $rewriteFromId = null): void
    {
        $cssTarget = ThemeConvention::appCssPath($id);
        $themeRoot = dirname($cssTarget);
        $viewsRoot = ThemeConvention::appViewsPath($id);

        File::ensureDirectoryExists($themeRoot);
        File::ensureDirectoryExists($viewsRoot);

        if (! is_file($sourceDirectory.'/theme.css')) {
            throw new \InvalidArgumentException('Archive is missing theme.css.');
        }

        File::copy($sourceDirectory.'/theme.css', $cssTarget);

        $assetsDirectory = $sourceDirectory.'/assets';

        if (is_dir($assetsDirectory)) {
            foreach (File::allFiles($assetsDirectory) as $file) {
                $target = $themeRoot.'/'.$file->getRelativePathname();
                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
            }
        }

        $viewsDirectory = $sourceDirectory.'/views';

        if (is_dir($viewsDirectory)) {
            foreach (File::allFiles($viewsDirectory) as $file) {
                $target = $viewsRoot.'/'.$file->getRelativePathname();
                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
            }
        }

        if (filled($rewriteFromId) && $rewriteFromId !== $id) {
            ThemeConvention::rewriteThemeIdInDirectory($themeRoot, $rewriteFromId, $id);
            ThemeConvention::rewriteThemeIdInDirectory($viewsRoot, $rewriteFromId, $id);
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected static function resolveRewriteFromId(array $manifest, string $targetId): ?string
    {
        $sourceId = Str::kebab((string) ($manifest['id'] ?? ''));

        if ($sourceId === '' || $sourceId === $targetId) {
            return null;
        }

        return $sourceId;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    protected static function normalizeDefinition(
        string $id,
        array $definition,
        ?string $labelOverride = null,
        ?string $renamedFrom = null,
    ): array {
        $label = filled($labelOverride)
            ? $labelOverride
            : (filled($definition['label'] ?? null)
                ? (string) $definition['label']
                : str($id)->headline()->toString());

        if ($renamedFrom !== null && $renamedFrom !== $id && $labelOverride === null) {
            $label .= ' ('.__('vpress::settings.theme_copy_suffix').')';
        }

        $normalized = [
            'label' => $label,
            'description' => (string) ($definition['description'] ?? "Imported {$label} theme."),
            'type' => (string) ($definition['type'] ?? 'marketing'),
            'capabilities' => is_array($definition['capabilities'] ?? null) ? $definition['capabilities'] : ['landing'],
            'layouts' => [],
            'css' => ThemeConvention::appCssRelativePath($id),
        ];

        foreach (['home', 'landing', 'page', 'article', 'section_index'] as $layout) {
            if (is_file(ThemeConvention::appViewsPath($id)."/layouts/{$layout}.blade.php")) {
                $normalized['layouts'][$layout] = ThemeConvention::appLayoutView($id, $layout);
            }
        }

        if (is_array($definition['chrome'] ?? null) && $definition['chrome'] !== []) {
            $normalized['chrome'] = $definition['chrome'];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $colors
     */
    public static function importAppearanceColorsFor(string $id, ?array $colors): bool
    {
        return self::importAppearanceColors($id, $colors);
    }

    /**
     * @param  array<string, mixed>|null  $colors
     */
    protected static function importAppearanceColors(string $id, ?array $colors): bool
    {
        if (! is_array($colors) || $colors === []) {
            return false;
        }

        $allColors = VpressSettings::get('sub_theme_colors', []);

        if (! is_array($allColors)) {
            $allColors = [];
        }

        $allColors[$id] = $colors;

        VpressSettings::saveData([
            'sub_theme_colors' => ThemePalette::normalize($allColors),
        ]);

        return true;
    }
}
