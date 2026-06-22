<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Voodflow\Vpress\Support\SubThemeScaffolder;
use Voodflow\Vpress\Support\ThemeConvention;

class MakeSubThemeCommand extends Command
{
    protected $signature = 'vpress:make-subtheme
                            {name : Sub-theme identifier (kebab-case, e.g. magazine)}
                            {--label= : Human-readable label}
                            {--force : Overwrite existing theme files}';

    protected $description = 'Scaffold a custom vpress sub-theme in the host application';

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
            $this->components->info("Registered \"{$result->id}\" in config/vpress.php.");
        } else {
            $this->components->warn('Could not update config/vpress.php automatically — add the theme manually.');
        }

        if ($result->importAppended) {
            $this->components->info('Added @import to the vpress theme bundle.');
        } else {
            $this->components->warn('Could not append @import automatically — run npm run build after adding the import.');
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
