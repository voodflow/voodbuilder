<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Str;

final class SubThemeCloner
{
    public static function suggestCloneId(string $sourceId): string
    {
        return SubThemeImporter::suggestAvailableId(Str::kebab($sourceId).'-copy');
    }

    public static function clone(
        string $sourceId,
        string $targetId,
        ?string $label = null,
        bool $importColors = true,
    ): SubThemeImportResult {
        $sourceId = Str::kebab($sourceId);
        $targetId = Str::kebab($targetId);

        if ($targetId === '' || $targetId === 'default' || ! preg_match('/^[a-z][a-z0-9-]*$/', $targetId)) {
            return new SubThemeImportResult(false, $targetId, 'Invalid theme id.');
        }

        if (SubThemeImporter::themeExists($targetId)) {
            return new SubThemeImportResult(
                false,
                $targetId,
                "Theme \"{$targetId}\" already exists. Choose another id.",
            );
        }

        if (SubThemeLocator::resolve($sourceId) === null) {
            return new SubThemeImportResult(
                false,
                $targetId,
                "Source theme \"{$sourceId}\" has no exportable files.",
            );
        }

        $archivePath = storage_path('app/vpress-theme-exports/.clone-'.uniqid('', true).'.zip');

        try {
            SubThemeExporter::export($sourceId, $archivePath);

            $result = SubThemeImporter::import(
                archivePath: $archivePath,
                force: false,
                importColors: $importColors,
                targetId: $targetId,
                renameOnConflict: false,
                label: $label,
            );

            if ($result->success && $importColors && ! $result->colorsImported) {
                $sourceColors = SubThemeLocator::appearanceColorsFor($sourceId);

                if ($sourceColors !== null) {
                    SubThemeImporter::importAppearanceColorsFor($result->id, $sourceColors);
                    $result = new SubThemeImportResult(
                        success: true,
                        id: $result->id,
                        configRegistered: $result->configRegistered,
                        importAppended: $result->importAppended,
                        colorsImported: true,
                        cssPath: $result->cssPath,
                        renamedFrom: $result->renamedFrom,
                    );
                }
            }

            return $result;
        } catch (\Throwable $exception) {
            return new SubThemeImportResult(false, $targetId, $exception->getMessage());
        } finally {
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
        }
    }
}
