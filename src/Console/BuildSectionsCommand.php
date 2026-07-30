<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Voodflow\Voodbuilder\Support\Editor\SectionBlocksCatalogBuilder;
use Voodflow\Voodbuilder\Support\Editor\SoundmitEditorLanding;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

/**
 * Artisan command: Build Sections.
 */
class BuildSectionsCommand extends Command
{
    protected $signature = 'voodbuilder:build-sections
                            {--theme=indigo : Accent color for the upstream section source}';

    protected $description = 'Build the Voodbuilder Editor section block catalog (section-blocks.json)';

    public function handle(): int
    {
        $script = VoodbuilderPaths::packagePath().'/scripts/build-section-source.mjs';

        if (! is_file($script)) {
            $this->components->error('Missing build script: '.$script);

            return self::FAILURE;
        }

        if (! $this->sectionSourceDependenciesAreInstalled()) {
            $this->components->error('Missing npm packages for section catalog export.');
            $this->line('  Run from the Laravel app root:');
            $this->line('  npm install -D esbuild react react-dom prop-types');

            return self::FAILURE;
        }

        $theme = (string) $this->option('theme');

        $process = new Process(
            ['node', $script],
            base_path(),
            ['VOODBUILDER_SECTION_THEME' => $theme],
            null,
            300,
        );

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->components->error('Section source export failed.');
            $this->line('  Ensure these dev dependencies are installed in the host app:');
            $this->line('  npm install -D esbuild react react-dom prop-types');

            return self::FAILURE;
        }

        if (! SectionBlocksCatalogBuilder::sourceIsAvailable()) {
            $this->components->error('Intermediate catalog file was not created.');

            return self::FAILURE;
        }

        $sectionCount = (new SectionBlocksCatalogBuilder)->write();

        $this->components->info('Voodbuilder section catalog ready ('.$sectionCount.' blocks): '.SectionBlocksCatalogBuilder::outputPath());
        SoundmitEditorLanding::writeUtilitiesCatalog();
        $this->components->warn('Run `npm run build` so section-utilities.css is compiled for the canvas.');

        return self::SUCCESS;
    }

    protected function sectionSourceDependenciesAreInstalled(): bool
    {
        foreach (['esbuild', 'react', 'react-dom', 'prop-types'] as $package) {
            if (! is_dir(base_path('node_modules/'.$package))) {
                return false;
            }
        }

        return true;
    }
}
