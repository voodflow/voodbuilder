<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Voodflow\Vpress\Support\GrapesJs\TailblocksGrapesJsBlocks;
use Voodflow\Vpress\Support\VpressPaths;

class BuildTailblocksCommand extends Command
{
    protected $signature = 'vpress:build-tailblocks
                            {--theme=indigo : Tailblocks accent color}';

    protected $description = 'Export Tailblocks components into GrapesJS block catalog JSON';

    public function handle(): int
    {
        $script = VpressPaths::packagePath().'/scripts/build-tailblocks.mjs';

        if (! is_file($script)) {
            $this->components->error('Missing build script: '.$script);

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
            $this->components->error('Tailblocks export failed. Run `npm install -D esbuild` in the host app.');

            return self::FAILURE;
        }

        if (! TailblocksGrapesJsBlocks::isAvailable()) {
            $this->components->error('Catalog file was not created.');

            return self::FAILURE;
        }

        $this->components->info('Tailblocks catalog ready at: '.TailblocksGrapesJsBlocks::catalogPath());
        $this->components->warn('Run `npm run build` so tailblocks-utilities.css is compiled for the canvas.');

        return self::SUCCESS;
    }
}
