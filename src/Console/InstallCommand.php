<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Voodflow\Vpress\Support\ConfigureTutorialsForVpress;
use Voodflow\Vpress\Support\DisableFilamentCookieBanner;

class InstallCommand extends Command
{
    protected $signature = 'vpress:install
                            {--force : Overwrite already published files}
                            {--skip-migrate : Publish configs and migrations without running migrate}
                            {--skip-seed : Skip seeding default Vpress data}';

    protected $description = 'Publish Vpress and dependency configs/migrations, then run migrate and seed';

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
        'vpress-config' => 'voodflow/vpress',
    ];

    public function handle(): int
    {
        $this->components->info('Installing voodflow/vpress...');

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

        $this->configureTutorialsIntegration();

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

        if (! $this->option('skip-seed') && class_exists(\Voodflow\Vpress\Database\Seeders\VpressSeeder::class)) {
            $this->components->info('Seeding default Vpress data...');
            $this->call('db:seed', ['--class' => \Voodflow\Vpress\Database\Seeders\VpressSeeder::class]);
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

    protected function configureTutorialsIntegration(): void
    {
        if (! InstalledVersions::isInstalled('voodflow/tutorials')) {
            return;
        }

        if (ConfigureTutorialsForVpress::apply($this->option('force'))) {
            $this->components->info('Updated config/tutorials.php to use vpress layouts.');
        } else {
            $this->components->warn('config/tutorials.php already uses vpress layouts (or file missing).');
        }
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

        $this->components->warn('Found published Vpress migrations in database/migrations.');
        $this->components->warn('Vpress already loads migrations from the package — you can remove the published copies to avoid duplicates.');
    }

    protected function finish(int $status): int
    {
        if ($status !== self::SUCCESS) {
            return $status;
        }

        $this->newLine();
        $this->components->info('Complete these steps in your host app:');
        $this->newLine();

        $this->line('  1. Register the Filament plugin (if not already):');
        $this->line('     ->plugins([\\Voodflow\\Vpress\\VpressPlugin::make()])');
        $this->newLine();

        $this->line('  2. Frontend assets (Vite — due passi distinti):');
        $this->newLine();
        $this->line('     a) Terminale — installa dipendenze:');
        $this->line('        npm install -D @fontsource-variable/inter @fontsource/jetbrains-mono tailwindcss @tailwindcss/vite');
        $this->newLine();
        $this->line('     b) File vite.config.js — aggiungi theme.css all\'array input (NON eseguirlo in bash):');
        $this->line("        'packages/voodflow/vpress/resources/css/theme.css'");
        $this->line('        // stesso path anche in config/vpress.php → assets.vite');
        $this->newLine();
        $this->line('     c) Sul Mac (cartella app/) — NON nel container Docker:');
        $this->line('        npm run build   # oppure npm run dev');
        $this->line('        # composer/artisan → container | npm → host Mac');
        $this->newLine();

        $this->line('  3. Customize config/vpress.php and manage Site → Settings in Filament.');
        $this->newLine();

        $this->components->success('voodflow/vpress installed successfully.');

        return self::SUCCESS;
    }
}
