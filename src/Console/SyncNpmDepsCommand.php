<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Voodflow\Voodbuilder\Support\ConfigureNpmForVoodbuilder;
use Voodflow\Voodbuilder\Support\ConfigureViteForVoodbuilder;

class SyncNpmDepsCommand extends Command
{
    protected $signature = 'voodbuilder:sync-npm-deps
                            {--force : Rewrite package.json even when dependencies are already present}
                            {--install : Run npm install after patching package.json}
                            {--build : Run npm run build after npm install}';

    protected $description = 'Patch host package.json and vite.config.js with required voodbuilder frontend dependencies';

    public function handle(): int
    {
        if (! is_file(base_path('package.json'))) {
            $this->components->error('package.json not found in the application root.');

            return self::FAILURE;
        }

        $added = ConfigureNpmForVoodbuilder::apply($this->option('force'));
        $missing = ConfigureNpmForVoodbuilder::missingFromPackageJson();

        if ($added !== []) {
            $this->components->info('Updated package.json with npm packages: '.implode(', ', $added));
        } elseif ($missing === []) {
            $this->components->info('package.json already includes required voodbuilder npm packages.');
        } else {
            $this->components->warn('Some required npm packages are still missing: '.implode(', ', $missing));
        }

        if (is_file(base_path('vite.config.js'))) {
            if (ConfigureViteForVoodbuilder::apply($this->option('force'))) {
                $this->components->info('Updated vite.config.js with voodbuilder theme and GrapesJS entries.');
            }
        } else {
            $this->components->warn('vite.config.js not found — add Voodbuilder Vite entries manually.');
        }

        if (! $this->option('install')) {
            $this->components->warn('Run `npm install --legacy-peer-deps` and `npm run build`, or pass `--install` / `--build`.');

            return self::SUCCESS;
        }

        if (! $this->npmIsAvailable()) {
            $this->components->error('npm not found on PATH.');

            return self::FAILURE;
        }

        $this->components->info('Running npm install --legacy-peer-deps...');

        $install = Process::path(base_path())
            ->timeout(600)
            ->run('npm install --legacy-peer-deps');

        if (! $install->successful()) {
            $this->components->error('npm install failed.');
            $this->line($install->errorOutput());

            return self::FAILURE;
        }

        if (! $this->option('build')) {
            $this->components->info('npm install completed. Run `npm run build` when ready.');

            return self::SUCCESS;
        }

        $this->components->info('Running npm run build...');

        $build = Process::path(base_path())
            ->timeout(600)
            ->run('npm run build');

        if (! $build->successful()) {
            $this->components->error('npm run build failed.');
            $this->line($build->errorOutput());

            return self::FAILURE;
        }

        $this->components->success('Frontend dependencies synced and assets built successfully.');

        return self::SUCCESS;
    }

    protected function npmIsAvailable(): bool
    {
        return Process::run('npm --version')->successful();
    }
}
