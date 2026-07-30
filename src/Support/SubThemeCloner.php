<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Str;

/**
 * Sub Theme Cloner.
 */
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

        if ($targetId === '' || in_array($targetId, ['default', 'docs'], true) || ! preg_match('/^[a-z][a-z0-9-]*$/', $targetId)) {
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
            if (! app(SubThemeRegistry::class)->exists($sourceId)) {
                return new SubThemeImportResult(
                    false,
                    $targetId,
                    "Source theme \"{$sourceId}\" was not found.",
                );
            }

            return self::cloneFromDefinition($sourceId, $targetId, $label, $importColors);
        }

        $archivePath = storage_path('app/voodbuilder-theme-exports/.clone-'.uniqid('', true).'.zip');

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

    protected static function cloneFromDefinition(
        string $sourceId,
        string $targetId,
        ?string $label,
        bool $importColors,
    ): SubThemeImportResult {
        $definition = SubThemeLocator::definitionFor($sourceId);
        $resolvedLabel = filled($label)
            ? trim((string) $label)
            : (($definition['label'] ?? $sourceId).' copy');

        $scaffold = SubThemeScaffolder::createFromDefinition($targetId, $resolvedLabel, $definition);

        if (! $scaffold->success) {
            return new SubThemeImportResult(false, $targetId, $scaffold->error);
        }

        $colorsImported = false;

        if ($importColors) {
            $sourceColors = SubThemeLocator::appearanceColorsFor($sourceId);

            if ($sourceColors !== null) {
                SubThemeImporter::importAppearanceColorsFor($scaffold->id, $sourceColors);
                $colorsImported = true;
            }
        }

        return new SubThemeImportResult(
            success: true,
            id: $scaffold->id,
            configRegistered: $scaffold->configRegistered,
            importAppended: $scaffold->importAppended,
            colorsImported: $colorsImported,
            cssPath: $scaffold->cssPath,
        );
    }
}
