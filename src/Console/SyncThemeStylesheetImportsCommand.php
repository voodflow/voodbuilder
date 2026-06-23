<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;
use Voodflow\Vpress\Support\SyncThemeStylesheetImports;

class SyncThemeStylesheetImportsCommand extends Command
{
    protected $signature = 'vpress:sync-theme-imports';

    protected $description = 'Prune missing app theme @imports from the vpress CSS bundle and add imports for existing app themes';

    public function handle(): int
    {
        if (SyncThemeStylesheetImports::sync()) {
            $this->components->info('Updated theme.css imports.');

            return self::SUCCESS;
        }

        $this->components->info('Theme.css imports are already in sync.');

        return self::SUCCESS;
    }
}
