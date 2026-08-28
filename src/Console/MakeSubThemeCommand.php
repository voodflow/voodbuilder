<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Support\SubThemeScaffolder;
use Voodflow\Voodbuilder\Support\ThemeConvention;

/**
 * Artisan command: Make Sub Theme.
 */
class MakeSubThemeCommand extends Command
{
    protected $signature = 'voodbuilder:make-subtheme
                            {name : Sub-theme identifier (kebab-case, e.g. magazine)}
                            {--label= : Human-readable label}
                            {--force : Overwrite existing theme files}';

    protected $description = 'Scaffold a custom voodbuilder sub-theme in the host application';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $label = (string) ($this->option('label') ?: str(Str::kebab($name))->headline());
        $force = (bool) $this->option('force');

        $result = SubThemeScaffolder::create($name, $label, $force);

        if (! $result->success) {
            $this->components->error($result->error ?? 'Could not create sub-theme.');

            return self::FAILURE;
        }

        if ($result->configRegistered) {
            $this->components->info("Registered \"{$result->id}\" in config/voodbuilder.php.");
        } else {
            $this->components->warn('Could not update config/voodbuilder.php automatically — add the theme manually.');
        }

        if ($result->importAppended) {
            $this->components->info('Registered CSS as a Vite input (loaded only when this sub-theme is active).');
        } else {
            $this->components->warn('Add the theme CSS path to vite.config.js `input`, then run npm run build.');
        }

        $viewsRoot = ThemeConvention::appViewsPath($result->id).'/layouts';

        $this->newLine();
        $this->components->info("Sub-theme \"{$result->id}\" created.");
        $this->line("  CSS:     {$result->cssPath}");
        $this->line("  Layouts: {$viewsRoot}/");
        $this->line('Customize colors in Admin → Settings → Layouts, or edit the theme CSS file.');

        return self::SUCCESS;
    }
}
