<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;
use Voodflow\Vpress\Support\ThemePreset;
use Voodflow\Vpress\Support\ThemePresetManager;

class ThemePresetCommand extends Command
{
    protected $signature = 'vpress:theme
                            {action : list, export, or import}
                            {preset? : Preset id, or output basename for export}
                            {--output= : Output directory or file path for export}
                            {--apply : Apply preset after import}
                            {--no-save : Do not store imported preset in site settings}';

    protected $description = 'List, export, or import Vpress theme presets';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->listPresets(),
            'export' => $this->exportPreset(),
            'import' => $this->importPreset(),
            default => $this->invalidAction(),
        };
    }

    protected function listPresets(): int
    {
        $rows = ThemePresetManager::all()
            ->map(fn (ThemePreset $preset): array => [
                $preset->id,
                $preset->label,
                $preset->bundled ? 'bundled' : 'custom',
                $preset->sitePagesTheme,
            ])
            ->all();

        if ($rows === []) {
            $this->components->warn('No theme presets found.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Label', 'Source', 'Site pages theme'], $rows);

        return self::SUCCESS;
    }

    protected function exportPreset(): int
    {
        $presetId = $this->argument('preset');

        $preset = filled($presetId)
            ? ThemePresetManager::find((string) $presetId)
            : ThemePresetManager::snapshotFromSettings(
                'export-'.now()->format('Y-m-d-His'),
                'Exported '.now()->toDateTimeString(),
            );

        if ($preset === null) {
            $this->components->error('Theme preset not found.');

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: base_path("{$preset->id}.json"));

        if (is_dir($output)) {
            $output = rtrim($output, '/')."/{$preset->id}.json";
        }

        ThemePresetManager::exportToFile($preset, $output);
        $this->components->info("Exported theme preset to {$output}");

        return self::SUCCESS;
    }

    protected function importPreset(): int
    {
        $path = (string) ($this->option('output') ?: $this->argument('preset'));

        if (! is_file($path)) {
            $this->components->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $preset = ThemePresetManager::importFromFile(
                $path,
                apply: (bool) $this->option('apply'),
                saveCustom: ! $this->option('no-save'),
            );
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Imported theme preset \"{$preset->label}\" ({$preset->id}).");

        if ($this->option('apply')) {
            $this->components->info('Preset applied to site settings.');
        }

        return self::SUCCESS;
    }

    protected function invalidAction(): int
    {
        $this->components->error('Supported actions: list, export, import');

        return self::FAILURE;
    }
}
