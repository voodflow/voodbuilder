<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Voodflow\Vpress\Support\AppendThemeStylesheetImport;
use Voodflow\Vpress\Support\ConfigureSubThemesForVpress;
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
        $themeRoot = dirname(ThemeConvention::appCssPath($id));
        $viewsRoot = ThemeConvention::appViewsPath($id).'/layouts';
        $cssPath = ThemeConvention::appCssPath($id);
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
        $this->writeStub('layouts/landing.blade.php.stub', "{$viewsRoot}/landing.blade.php", $replacements, $force);

        $cssRelative = ThemeConvention::appCssRelativePath($id);
        $definition = [
            'label' => $label,
            'description' => "Custom {$label} landing theme.",
            'type' => 'marketing',
            'capabilities' => ['landing'],
            'layouts' => [
                'home' => ThemeConvention::appLayoutView($id, 'home'),
                'landing' => ThemeConvention::appLayoutView($id, 'landing'),
                'page' => ThemeConvention::appLayoutView($id, 'page'),
            ],
            'css' => $cssRelative,
        ];

        if (ConfigureSubThemesForVpress::registerInConfig($id, $definition)) {
            $this->components->info("Registered \"{$id}\" in config/vpress.php.");
        } else {
            $this->components->warn('Could not update config/vpress.php automatically — add the theme manually.');
        }

        if (AppendThemeStylesheetImport::append($cssPath)) {
            $this->components->info('Added @import to the vpress theme bundle (packages/voodflow/vpress/resources/css/theme.css).');
        } else {
            $this->components->warn('Could not append @import automatically — add your theme CSS to the vpress bundle, then run npm run build.');
        }

        $this->newLine();
        $this->components->info("Sub-theme \"{$id}\" created.");
        $this->line("  CSS:     {$cssPath}");
        $this->line("  Layouts: {$viewsRoot}/");
        $this->line('Assign it in Admin → Settings → Theme, or per page in Pages.');

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
