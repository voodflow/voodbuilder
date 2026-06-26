<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Symfony\Component\Process\Process;

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
}
