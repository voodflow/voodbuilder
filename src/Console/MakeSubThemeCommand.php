<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Voodflow\Vpress\Support\ConfigureSubThemesForVpress;
use Voodflow\Vpress\Support\ConfigureViteForSubTheme;

class MakeSubThemeCommand extends Command
{
    protected $signature = 'vpress:make-subtheme
                            {name : Sub-theme identifier (kebab-case, e.g. magazine)}
                            {--label= : Human-readable label}
                            {--force : Overwrite existing theme files}';

    protected $description = 'Scaffold a custom vpress sub-theme in the host application';

    public function handle(): int
    {
        $id = Str::kebab((string) $this->argument('name'));

        if ($id === '' || $id === 'default') {
            $this->components->error('Choose a name other than "default".');

            return self::FAILURE;
        }

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $id)) {
            $this->components->error('Use kebab-case letters, numbers, and hyphens only.');

            return self::FAILURE;
        }

        $label = (string) ($this->option('label') ?: str($id)->headline());
        $themeRoot = resource_path("vpress/themes/{$id}");
        $viewsRoot = resource_path("views/vpress/themes/{$id}/layouts");
        $cssPath = "{$themeRoot}/theme.css";
        $force = (bool) $this->option('force');

        if (File::isDirectory($themeRoot) && ! $force) {
            $this->components->error("Theme directory already exists: {$themeRoot}");
            $this->line('Use --force to overwrite generated files.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($themeRoot);
        File::ensureDirectoryExists($viewsRoot);

        $replacements = [
            '{{ id }}' => $id,
            '{{ name }}' => $label,
        ];

        $this->writeStub('theme.css.stub', $cssPath, $replacements, $force);
        $this->writeStub('layouts/page.blade.php.stub', "{$viewsRoot}/page.blade.php", $replacements, $force);
        $this->writeStub('layouts/home.blade.php.stub', "{$viewsRoot}/home.blade.php", $replacements, $force);

        $cssRelative = "resources/vpress/themes/{$id}/theme.css";
        $definition = [
            'label' => $label,
            'description' => "Custom {$label} sub-theme.",
            'layouts' => [
                'home' => "vpress.themes.{$id}.layouts.home",
                'page' => "vpress.themes.{$id}.layouts.page",
            ],
            'css' => $cssRelative,
        ];

        if (ConfigureSubThemesForVpress::registerInConfig($id, $definition)) {
            $this->components->info("Registered \"{$id}\" in config/vpress.php.");
        } else {
            $this->components->warn('Could not update config/vpress.php automatically — add the theme manually.');
        }

        if (ConfigureViteForSubTheme::appendCssEntry($cssRelative)) {
            $this->components->info("Added {$cssRelative} to vite.config.js.");
        } else {
            $this->components->warn("Add {$cssRelative} to your Vite input array, then run npm run build.");
        }

        $this->newLine();
        $this->components->info("Sub-theme \"{$id}\" created.");
        $this->line("  CSS:     {$cssPath}");
        $this->line("  Layouts: {$viewsRoot}/");
        $this->line('Assign it in Admin → Site → Settings (site default) or per page in Pages.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function writeStub(string $stub, string $destination, array $replacements, bool $force): void
    {
        if (File::exists($destination) && ! $force) {
            return;
        }

        $stubPath = __DIR__.'/../../stubs/sub-theme/'.$stub;
        $contents = str_replace(
            array_keys($replacements),
            array_values($replacements),
            File::get($stubPath),
        );

        File::put($destination, $contents);
    }
}
