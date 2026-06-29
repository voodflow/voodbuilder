<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Voodflow\Voodbuilder\Support\GrapesJs\SoundmitGrapesJsLanding;
use Voodflow\Voodbuilder\Support\GrapesJs\TailblocksGrapesJsBlocks;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

class BuildTailblocksCommand extends Command
{
    protected $signature = 'voodbuilder:build-tailblocks
                            {--theme=indigo : Tailblocks accent color}';

    protected $description = 'Export Tailblocks components into GrapesJS block catalog JSON';

    public function handle(): int
    {
        $script = VoodbuilderPaths::packagePath().'/scripts/build-tailblocks.mjs';

        if (! is_file($script)) {
            $this->components->error('Missing build script: '.$script);

            return self::FAILURE;
        }

        if (! $this->tailblocksDependenciesAreInstalled()) {
            $this->components->error('Missing npm packages for Tailblocks export.');
            $this->line('  Run from the Laravel app root:');
            $this->line('  npm install -D esbuild react react-dom prop-types');

            return self::FAILURE;
        }

        $theme = (string) $this->option('theme');

        $process = new Process(
            ['node', $script],
            base_path(),
            ['TAILBLOCKS_THEME' => $theme],
            null,
            300,
        );

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->components->error('Tailblocks export failed.');
            $this->line('  Ensure these dev dependencies are installed in the host app:');
            $this->line('  npm install -D esbuild react react-dom prop-types');

            return self::FAILURE;
        }

        if (! TailblocksGrapesJsBlocks::isAvailable()) {
            $this->components->error('Catalog file was not created.');

            return self::FAILURE;
        }

        $this->components->info('Tailblocks catalog ready at: '.TailblocksGrapesJsBlocks::catalogPath());
        SoundmitGrapesJsLanding::writeUtilitiesCatalog();
        $this->components->warn('Run `npm run build` so tailblocks-utilities.css is compiled for the canvas.');

        return self::SUCCESS;
    }

    protected function tailblocksDependenciesAreInstalled(): bool
    {
        foreach (['esbuild', 'react', 'react-dom', 'prop-types'] as $package) {
            if (! is_dir(base_path('node_modules/'.$package))) {
                return false;
            }
        }

        return true;
    }
}
