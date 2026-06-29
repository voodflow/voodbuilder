<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;
use ZipArchive;

final class SubThemeExporter
{
    public const MANIFEST_FILE = 'manifest.json';

    public const FORMAT = 'voodbuilder-sub-theme';

    public const VERSION = 1;

    public static function export(string $id, string $destinationPath): string
    {
        $location = SubThemeLocator::resolve($id);

        if ($location === null) {
            throw new \InvalidArgumentException("Theme \"{$id}\" has no exportable files.");
        }

        $manifest = self::buildManifest($id, $location);
        $temporaryDirectory = self::stageArchive($location, $manifest);
        $archivePath = self::createZip($temporaryDirectory, $destinationPath);

        File::deleteDirectory($temporaryDirectory);

        return $archivePath;
    }

    public static function defaultArchivePath(string $id): string
    {
        $filename = $id.'-'.now()->format('Y-m-d-His').'.zip';

        return storage_path('app/voodbuilder-theme-exports/'.$filename);
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildManifest(string $id, SubThemeLocation $location): array
    {
        $definition = SubThemeLocator::definitionFor($id);
        $definition['css'] = ThemeConvention::appCssRelativePath($id);
        $definition['layouts'] = self::appLayoutViews($id, $definition['layouts'] ?? []);

        $manifest = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'id' => $id,
            'source' => $location->origin,
            'definition' => $definition,
        ];

        $colors = SubThemeLocator::appearanceColorsFor($id);

        if ($colors !== null) {
            $manifest['appearance'] = [
                'colors' => $colors,
            ];
        }

        return $manifest;
    }

    /**
     * @param  array<string, string>  $layouts
     * @return array<string, string>
     */
    protected static function appLayoutViews(string $id, array $layouts): array
    {
        $normalized = [];

        foreach (['home', 'landing', 'page', 'article', 'section_index'] as $layout) {
            $normalized[$layout] = $layouts[$layout] ?? ThemeConvention::appLayoutView($id, $layout);
        }

        return array_filter($normalized);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected static function stageArchive(SubThemeLocation $location, array $manifest): string
    {
        $temporaryDirectory = storage_path('app/voodbuilder-theme-exports/.staging-'.uniqid('', true));
        File::ensureDirectoryExists($temporaryDirectory);

        File::put(
            $temporaryDirectory.'/'.self::MANIFEST_FILE,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
        );

        File::copy($location->cssPath, $temporaryDirectory.'/theme.css');

        if (is_dir($location->themeRoot)) {
            foreach (File::allFiles($location->themeRoot) as $file) {
                if ($file->getFilename() === 'theme.css') {
                    continue;
                }

                $relative = $file->getRelativePathname();
                $target = $temporaryDirectory.'/assets/'.$relative;
                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
            }
        }

        if (is_dir($location->viewsRoot)) {
            foreach (File::allFiles($location->viewsRoot) as $file) {
                $relative = $file->getRelativePathname();
                $target = $temporaryDirectory.'/views/'.$relative;
                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
            }
        }

        return $temporaryDirectory;
    }

    protected static function createZip(string $sourceDirectory, string $destinationPath): string
    {
        File::ensureDirectoryExists(dirname($destinationPath));

        if (is_file($destinationPath)) {
            File::delete($destinationPath);
        }

        $zip = new ZipArchive;

        if ($zip->open($destinationPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create archive: {$destinationPath}");
        }

        foreach (File::allFiles($sourceDirectory) as $file) {
            $zip->addFile(
                $file->getPathname(),
                $file->getRelativePathname(),
            );
        }

        $zip->close();

        return $destinationPath;
    }
}
