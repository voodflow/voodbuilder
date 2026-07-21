<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Voodflow\Voodbuilder\Support\SyncThemeStylesheetImports;

class SyncThemeStylesheetImportsCommand extends Command
{
    protected $signature = 'voodbuilder:sync-theme-imports';

    protected $description = 'Prune stale/missing theme @imports from the voodbuilder CSS core bundle';

    public function handle(): int
    {
        if (SyncThemeStylesheetImports::sync()) {
            $this->components->info('Updated theme.css imports (app sub-themes stay as Vite entries).');

            return self::SUCCESS;
        }

        $this->components->info('Theme.css imports are already in sync.');

        return self::SUCCESS;
    }

}
