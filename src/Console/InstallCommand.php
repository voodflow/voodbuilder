<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'vpress:install
                            {--no-migrate : Skip running migrations}';

    protected $description = 'Install the Voodflow Vpress plugin';

    public function handle(): int
    {
        $this->components->info('Publishing Vpress configuration…');

        $this->callSilent('vendor:publish', [
            '--tag' => 'vpress-config',
            '--force' => true,
        ]);

        if (! $this->option('no-migrate')) {
            $this->components->info('Running migrations…');
            $this->call('migrate', [
                '--path' => 'packages/voodflow/vpress/database/migrations',
                '--force' => true,
            ]);

            if (class_exists(\Spatie\LaravelSettings\LaravelSettingsServiceProvider::class)) {
                $this->callSilent('vendor:publish', [
                    '--provider' => 'Spatie\\LaravelSettings\\LaravelSettingsServiceProvider',
                    '--tag' => 'migrations',
                ]);
            }

            $this->call('migrate', ['--force' => true]);
        }

        if (class_exists(\Voodflow\Vpress\Database\Seeders\VpressSeeder::class)) {
            $this->components->info('Seeding default Vpress data…');
            $this->call('db:seed', ['--class' => \Voodflow\Vpress\Database\Seeders\VpressSeeder::class]);
        }

        $this->newLine();
        $this->components->info('Vpress is almost ready. Complete these steps in your host app:');
        $this->newLine();

        $this->line('  1. Register the Filament plugin in your panel provider:');
        $this->line('     ->plugins([\\Voodflow\\Vpress\\VpressPlugin::make()])');
        $this->newLine();

        $this->line('  2. Install self-hosted fonts and add the theme CSS entry to vite.config.js:');
        $this->line('     npm install -D @fontsource-variable/inter @fontsource/jetbrains-mono');
        $this->line("     'packages/voodflow/vpress/resources/css/theme.css'");
        $this->line('     npm run build');
        $this->newLine();

        $this->line('  3. Customize config/vpress.php (home fallback, assets) and seed navigation menus.');
        $this->newLine();

        $this->line('  4. Register custom RichEditor blocks from other packages:');
        $this->line('     Vpress::richContentBlock(\'Dynamic\', YourBlock::class);');
        $this->newLine();

        $this->components->success('Vpress installed.');

        return self::SUCCESS;
    }
}
