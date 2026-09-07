<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Symfony\Component\Process\Process;

/**
 * Optional developer helper: sync package stylesheet imports and rebuild Vite.
 *
 * App themes created in Theme Studio do not need this — they load as runtime CSS.
 */
final class ThemeAssetCompiler
{
    public static function compile(?string $workingDirectory = null): bool
    {
        SyncThemeStylesheetImports::sync();

        $workingDirectory ??= base_path();

        if (! is_file($workingDirectory.'/package.json')) {
            return false;
        }

        $process = new Process(
            ['npm', 'run', 'build'],
            $workingDirectory,
            null,
            null,
            180,
        );

        $process->run();

        return $process->isSuccessful();
    }

    /**
     * After Theme Studio create/clone/import: no Vite rebuild — app themes are runtime skins.
     */
    public static function scheduleCompile(?string $workingDirectory = null): void
    {
        SyncThemeStylesheetImports::sync();
    }
}
