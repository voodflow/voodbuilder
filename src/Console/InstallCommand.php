<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Voodflow\Voodbuilder\Database\Seeders\VoodbuilderSeeder;
use Voodflow\Voodbuilder\Support\ConfigureNpmForVoodbuilder;
use Voodflow\Voodbuilder\Support\ConfigureRoutesForVoodbuilder;
use Voodflow\Voodbuilder\Support\ConfigureViteForVoodbuilder;
use Voodflow\Voodbuilder\Support\ConfigureVtutsForVoodbuilder;
use Voodflow\Voodbuilder\Support\DisableFilamentCookieBanner;

class InstallCommand extends Command
{
    protected $signature = 'voodbuilder:install
                            {--force : Overwrite already published files}
                            {--skip-migrate : Publish configs and migrations without running migrate}
                            {--skip-seed : Skip seeding default Voodbuilder data}
                            {--skip-npm : Do not patch package.json or run npm install}
                            {--with-npm-build : Run npm run build after npm install}';

    protected $description = 'Publish Voodbuilder and dependency configs/migrations, then run migrate and seed';

    /**
     * Publish order matters: Spatie settings table must exist before cookie consent settings migrations run.
     *
     * @var array<string, string|null>
     */
    protected array $publishTags = [
        'config' => 'spatie/laravel-settings',
        'migrations' => 'spatie/laravel-settings',
        'cookie-consent-settings-migrations' => 'jeffersongoncalves/laravel-cookie-consent',
        'seo-config' => 'ralphjsmit/laravel-seo',
        'seo-migrations' => 'ralphjsmit/laravel-seo',
        'voodbuilder-config' => 'voodflow/voodbuilder',
    ];

    public function handle(): int
    {
        $previousLocale = app()->getLocale();
        $previousFallbackLocale = app()->getFallbackLocale();

        app()->setLocale('en');
        app()->setFallbackLocale('en');

        try {
            return $this->runInstall();
        } finally {
            app()->setLocale($previousLocale);
            app()->setFallbackLocale($previousFallbackLocale);
        }
    }

    protected function runInstall(): int
    {
        $this->components->info('Installing voodflow/voodbuilder...');

        $publishOptions = array_filter([
            '--force' => $this->option('force'),
        ]);

        foreach ($this->publishTags as $tag => $package) {
            if ($package !== null && ! InstalledVersions::isInstalled($package)) {
                $this->components->warn("Skipping {$tag}: package {$package} is not installed.");

                continue;
            }

            $this->components->info("Publishing {$tag}...");

            $arguments = ['--tag' => $tag, ...$publishOptions];

            if ($tag === 'config' && InstalledVersions::isInstalled('spatie/laravel-settings')) {
                $arguments['--provider'] = 'Spatie\\LaravelSettings\\LaravelSettingsServiceProvider';
            }

            if ($this->call('vendor:publish', $arguments) !== self::SUCCESS) {
                $this->components->error("Failed to publish {$tag}.");

                return self::FAILURE;
            }
        }

        $this->warnAboutPublishedMigrations();

        $this->publishNotificationsTableMigration();

        $this->configureVtutsIntegration();

        $this->configureRoutesIntegration();

        $this->configureViteIntegration();

        $this->configureNpmIntegration();

        $this->configureCookieConsentForFrontendOnly();

        if ($this->option('skip-migrate')) {
            $this->components->info('Skipped migrations (--skip-migrate). Run `php artisan migrate` when ready.');

            return $this->finish(self::SUCCESS);
        }

        if (! $this->settingsTableMigrationIsAvailable()) {
            $this->components->error('Missing create_settings_table migration.');
            $this->components->error('Run: php artisan vendor:publish --provider="Spatie\\LaravelSettings\\LaravelSettingsServiceProvider" --tag=migrations');

            return self::FAILURE;
        }

        $this->components->info('Running migrations...');

        if ($this->call('migrate', array_filter([
            '--force' => ! $this->input->isInteractive(),
        ])) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->option('skip-seed') && class_exists(VoodbuilderSeeder::class)) {
            $this->components->info('Seeding default Voodbuilder data...');
            $this->call('db:seed', ['--class' => VoodbuilderSeeder::class]);
        }

        return $this->finish(self::SUCCESS);
    }

    protected function settingsTableMigrationIsAvailable(): bool
    {
        $path = database_path('migrations');

        if (! is_dir($path)) {
            return false;
        }

        foreach (glob("{$path}/*create_settings_table.php") ?: [] as $file) {
            if (is_file($file)) {
                return true;
            }
        }

        return false;
    }

    protected function configureVtutsIntegration(): void
    {
        if (! InstalledVersions::isInstalled('voodflow/vtuts')) {
            return;
        }

        if (ConfigureVtutsForVoodbuilder::apply($this->option('force'))) {
            $this->components->info('Updated config/vtuts.php to use voodbuilder layouts.');
        } else {
            $this->components->warn('config/vtuts.php already uses voodbuilder layouts (or file missing).');
        }
    }

    protected function configureRoutesIntegration(): void
    {
        if (ConfigureRoutesForVoodbuilder::apply($this->option('force'))) {
            $this->components->info('Removed the default Laravel welcome route from routes/web.php.');
            $this->components->warn('The public homepage is now served by voodflow/voodbuilder (route name: home).');
        } else {
            $this->components->warn('routes/web.php already defers the homepage to voodbuilder (or no welcome route was found).');
        }
    }

    protected function configureViteIntegration(): void
    {
        if (! is_file(base_path('vite.config.js'))) {
            $this->components->warn('vite.config.js not found — add Voodbuilder Vite entries manually (see README → Vite & CSS).');

            return;
        }

        if (ConfigureViteForVoodbuilder::apply($this->option('force'))) {
            $this->components->info('Updated vite.config.js with voodbuilder theme and GrapesJS entries.');
        } else {
            $this->components->warn('vite.config.js already references voodbuilder Vite entries (or file could not be updated).');
        }
    }

    protected function configureNpmIntegration(): void
    {
        if ($this->option('skip-npm')) {
            $this->components->info('Skipped npm setup (--skip-npm).');

            return;
        }

        if (! is_file(base_path('package.json'))) {
            $this->components->warn('package.json not found — create it with `npm init` or copy from a Laravel app, then re-run voodbuilder:install.');

            return;
        }

        $added = ConfigureNpmForVoodbuilder::apply($this->option('force'));

        if ($added !== []) {
            $this->components->info('Updated package.json with npm packages: '.implode(', ', $added));
        } else {
            $this->components->warn('package.json already includes required voodbuilder npm packages.');
        }

        if (! $this->npmIsAvailable()) {
            $this->components->warn('npm not found on PATH — run `npm install` and `npm run build` manually when Node.js is available.');

            return;
        }

        $this->components->info('Running npm install...');

        $install = Process::path(base_path())
            ->timeout(600)
            ->run('npm install --legacy-peer-deps');

        if (! $install->successful()) {
            $this->components->error('npm install failed.');
            $this->line($install->errorOutput());

            return;
        }

        $this->components->info('npm install completed.');

        if (! $this->option('with-npm-build')) {
            $this->components->warn('Run `npm run build` (or `npm run dev`) to compile the public theme and GrapesJS editor.');
            $this->components->warn('Tip: pass `--with-npm-build` to compile assets during install.');

            return;
        }

        $this->components->info('Running npm run build...');

        $build = Process::path(base_path())
            ->timeout(600)
            ->run('npm run build');

        if (! $build->successful()) {
            $this->components->error('npm run build failed.');
            $this->line($build->errorOutput());

            return;
        }

        $this->components->info('Frontend assets built successfully.');
    }

    protected function npmIsAvailable(): bool
    {
        $result = Process::run('npm --version');

        return $result->successful();
    }

    protected function configureCookieConsentForFrontendOnly(): void
    {
        if (! InstalledVersions::isInstalled('jeffersongoncalves/filament-cookie-consent')) {
            return;
        }

        $composerPath = base_path('composer.json');

        if (! DisableFilamentCookieBanner::applyToComposerJson($composerPath)) {
            return;
        }

        $this->components->info('Disabled Filament auto-discovery for filament-cookie-consent (banner stays on public site).');
        $this->components->warn('Run `composer dump-autoload` so the admin panel stops loading the cookie banner.');
    }

    protected function publishNotificationsTableMigration(): void
    {
        if ($this->notificationsMigrationIsAvailable()) {
            return;
        }

        $this->components->info('Publishing Laravel notifications table migration...');

        if ($this->call('notifications:table') !== self::SUCCESS) {
            $this->components->warn('Could not publish notifications migration. Run `php artisan notifications:table` manually.');
        }
    }

    protected function notificationsMigrationIsAvailable(): bool
    {
        foreach (glob(database_path('migrations/*create_notifications_table.php')) ?: [] as $file) {
            if (is_file($file)) {
                return true;
            }
        }

        return false;
    }

    protected function warnAboutPublishedMigrations(): void
    {
        $publishedVpress = glob(database_path('migrations/*site_pages_table.php')) ?: [];

        if ($publishedVpress === []) {
            return;
        }

        $this->components->warn('Found published Voodbuilder migrations in database/migrations.');
        $this->components->warn('Voodbuilder already loads migrations from the package — you can remove the published copies to avoid duplicates.');
    }

    protected function finish(int $status): int
    {
        if ($status !== self::SUCCESS) {
            return $status;
        }

        $this->newLine();
        $this->components->info('Host app checklist (manual steps only):');
        $this->newLine();

        $this->line('  1. Filament panel — register the plugin once in your Panel provider:');
        $this->line('     ->plugins([\\Voodflow\\Voodbuilder\\VoodbuilderPlugin::make()])');
        $this->newLine();

        if ($this->option('skip-npm') || ! $this->npmIsAvailable()) {
            $this->line('  2. Frontend assets — from your Laravel app root:');
            $this->line('     php artisan voodbuilder:install --with-npm-build');
            $this->line('     (or: npm install && npm run build)');
            $this->newLine();
        } elseif (! $this->option('with-npm-build')) {
            $this->line('  2. Frontend assets — compile if you skipped the build step:');
            $this->line('     npm run build    # or: npm run dev');
            $this->newLine();
        }

        $this->line('  3. Customize config/voodbuilder.php and manage Site → Settings in Filament.');
        $this->newLine();

        $this->line('  Automatic setup already handled by voodbuilder:install:');
        $this->line('  - package.json npm dependencies (GrapesJS, Tailwind, fonts)');
        $this->line('  - vite.config.js theme + GrapesJS entries');
        $this->line('  - routes/web.php welcome route removal');
        $this->line('  - migrations, seed data, cookie-consent panel exclusion');
        $this->newLine();

        $this->components->success('voodflow/voodbuilder installed successfully.');

        return self::SUCCESS;
    }
}
