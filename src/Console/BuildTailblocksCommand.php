<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;

/**
 * @deprecated Use {@see BuildSectionsCommand}
 */
class BuildTailblocksCommand extends Command
{
    protected $signature = 'voodbuilder:build-tailblocks
                            {--theme=indigo : Deprecated — use voodbuilder:build-sections}';

    protected $description = 'Deprecated alias of voodbuilder:build-sections';

    protected $hidden = true;

    public function handle(): int
    {
        $this->components->warn('voodbuilder:build-tailblocks is deprecated. Use voodbuilder:build-sections instead.');

        return $this->call('voodbuilder:build-sections', [
            '--theme' => $this->option('theme'),
        ]);
    }
}
