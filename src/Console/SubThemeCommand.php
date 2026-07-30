<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Voodflow\Voodbuilder\Support\SubThemeCloner;
use Voodflow\Voodbuilder\Support\SubThemeExporter;
use Voodflow\Voodbuilder\Support\SubThemeImporter;
use Voodflow\Voodbuilder\Support\SubThemeLocator;

/**
 * Artisan command: Sub Theme.
 */
class SubThemeCommand extends Command
{
    protected $signature = 'voodbuilder:sub-theme
                            {action : list, export, import, or clone}
                            {theme? : Theme id for export/clone, or archive path for import}
                            {--output= : Output .zip path for export}
                            {--force : Overwrite an existing theme on import}
                            {--as= : Target theme id for import or clone}
                            {--label= : Display label for clone}
                            {--no-colors : Skip admin color overrides}
                            {--no-rename : Fail on import if the theme id already exists}';

    protected $description = 'Export or import Voodbuilder visual sub-themes (CSS, layouts, config)';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->listThemes(),
            'export' => $this->exportTheme(),
            'import' => $this->importTheme(),
            'clone' => $this->cloneTheme(),
            default => $this->invalidAction(),
        };
    }

    protected function listThemes(): int
    {
        $rows = SubThemeLocator::exportable()
            ->map(fn ($location): array => [
                $location->id,
                $location->origin,
                $location->cssPath,
            ])
            ->all();

        if ($rows === []) {
            $this->components->warn('No exportable sub-themes found.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Origin', 'CSS path'], $rows);

        return self::SUCCESS;
    }

    protected function exportTheme(): int
    {
        $themeId = (string) $this->argument('theme');

        if (blank($themeId)) {
            $this->components->error('Provide a theme id to export.');

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: SubThemeExporter::defaultArchivePath($themeId));

        if (is_dir($output)) {
            $output = rtrim($output, '/').'/'.$themeId.'.zip';
        }

        if (! str_ends_with(strtolower($output), '.zip')) {
            $output .= '.zip';
        }

        try {
            $archivePath = SubThemeExporter::export($themeId, $output);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Exported sub-theme \"{$themeId}\" to {$archivePath}");

        return self::SUCCESS;
    }

    protected function importTheme(): int
    {
        $archivePath = (string) ($this->option('output') ?: $this->argument('theme'));

        if (! is_file($archivePath)) {
            $this->components->error("Archive not found: {$archivePath}");

            return self::FAILURE;
        }

        $result = SubThemeImporter::import(
            archivePath: $archivePath,
            force: (bool) $this->option('force'),
            importColors: ! $this->option('no-colors'),
            targetId: $this->option('as'),
            renameOnConflict: ! $this->option('force') && ! $this->option('no-rename'),
        );

        if (! $result->success) {
            $this->components->error($result->error ?? 'Import failed.');

            return self::FAILURE;
        }

        $message = $result->renamedFrom !== null
            ? "Imported as \"{$result->id}\" (\"{$result->renamedFrom}\" already existed)."
            : "Imported sub-theme \"{$result->id}\".";

        $this->components->info($message);

        if ($result->configRegistered) {
            $this->components->info('Registered in config/voodbuilder.php.');
        }

        if ($result->colorsImported) {
            $this->components->info('Imported admin color overrides.');
        }

        if (! $result->importAppended) {
            $this->components->warn('Run npm run build to compile the imported theme stylesheet.');
        }

        return self::SUCCESS;
    }

    protected function cloneTheme(): int
    {
        $sourceId = (string) $this->argument('theme');

        if (blank($sourceId)) {
            $this->components->error('Provide a source theme id to clone.');

            return self::FAILURE;
        }

        $targetId = (string) ($this->option('as') ?: SubThemeCloner::suggestCloneId($sourceId));

        $result = SubThemeCloner::clone(
            sourceId: $sourceId,
            targetId: $targetId,
            label: $this->option('label'),
            importColors: ! $this->option('no-colors'),
        );

        if (! $result->success) {
            $this->components->error($result->error ?? 'Clone failed.');

            return self::FAILURE;
        }

        $this->components->info("Cloned \"{$sourceId}\" as \"{$result->id}\".");

        if (! $result->importAppended) {
            $this->components->warn('Run npm run build to compile the cloned theme stylesheet.');
        }

        return self::SUCCESS;
    }

    protected function invalidAction(): int
    {
        $this->components->error('Supported actions: list, export, import, clone');

        return self::FAILURE;
    }
}
