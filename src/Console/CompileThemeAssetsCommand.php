<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;
use Voodflow\Vpress\Support\ThemeAssetCompiler;

class CompileThemeAssetsCommand extends Command
{
    protected $signature = 'vpress:compile-theme-assets';

    protected $description = 'Sync theme stylesheet imports and rebuild the public Vite bundle';

    public function handle(): int
    {
        if (! ThemeAssetCompiler::compile()) {
            $this->components->error('Theme asset compilation failed. Run npm run build manually.');

            return self::FAILURE;
        }

        $this->components->info('Theme assets compiled.');

        return self::SUCCESS;
    }
}
