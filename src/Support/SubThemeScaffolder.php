<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class SubThemeScaffolder
{
    public static function create(string $name, string $label, bool $force = false): SubThemeScaffoldResult
    {
        $id = Str::kebab($name);

        if ($id === '' || in_array($id, ['default', 'docs'], true)) {
            return new SubThemeScaffoldResult(false, $id, 'Choose a name other than "docs".');
        }

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $id)) {
            return new SubThemeScaffoldResult(false, $id, 'Use lowercase letters, numbers, and hyphens only.');
        }

        if (app(SubThemeRegistry::class)->exists($id) && ! $force) {
            return new SubThemeScaffoldResult(false, $id, "Theme \"{$id}\" already exists.");
        }

        $label = trim($label) !== '' ? trim($label) : str($id)->headline()->toString();
        $themeRoot = dirname(ThemeConvention::appCssPath($id));
        $viewsRoot = ThemeConvention::appViewsPath($id).'/layouts';
        $cssPath = ThemeConvention::appCssPath($id);

        if (File::isDirectory($themeRoot) && ! $force) {
            return new SubThemeScaffoldResult(
                false,
                $id,
                "Theme directory already exists: {$themeRoot}",
            );
        }

        File::ensureDirectoryExists($themeRoot);
        File::ensureDirectoryExists($viewsRoot);

        $replacements = [
            '{{ id }}' => $id,
            '{{ name }}' => $label,
        ];

        self::writeStub('theme.css.stub', $cssPath, $replacements, $force);
        self::writeStub('layouts/page.blade.php.stub', "{$viewsRoot}/page.blade.php", $replacements, $force);
        self::writeStub('layouts/home.blade.php.stub', "{$viewsRoot}/home.blade.php", $replacements, $force);
        self::writeStub('layouts/landing.blade.php.stub', "{$viewsRoot}/landing.blade.php", $replacements, $force);

        $definition = [
            'label' => $label,
            'description' => "Custom {$label} theme.",
            'type' => 'marketing',
            'capabilities' => ['landing'],
            'layouts' => [
                'home' => ThemeConvention::appLayoutView($id, 'home'),
                'landing' => ThemeConvention::appLayoutView($id, 'landing'),
                'page' => ThemeConvention::appLayoutView($id, 'page'),
            ],
            'css' => ThemeConvention::appCssRelativePath($id),
        ];

        $configRegistered = ConfigureSubThemesForVoodbuilder::registerInConfig($id, $definition);
        $importAppended = AppendThemeStylesheetImport::append($cssPath);
        SyncThemeStylesheetImports::sync();

        app(SubThemeRegistry::class)->register($id, $definition);

        return new SubThemeScaffoldResult(
            success: true,
            id: $id,
            configRegistered: $configRegistered,
            importAppended: $importAppended,
            cssPath: $cssPath,
        );
    }

    public static function createDoc(string $name, string $label, bool $force = false): SubThemeScaffoldResult
    {
        $id = Str::kebab($name);

        if ($id === '' || in_array($id, ['default', 'docs'], true)) {
            return new SubThemeScaffoldResult(false, $id, 'Choose a name other than "docs".');
        }

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $id)) {
            return new SubThemeScaffoldResult(false, $id, 'Use lowercase letters, numbers, and hyphens only.');
        }

        if (app(SubThemeRegistry::class)->exists($id) && ! $force) {
            return new SubThemeScaffoldResult(false, $id, "Theme \"{$id}\" already exists.");
        }

        $label = trim($label) !== '' ? trim($label) : str($id)->headline()->toString();
        $themeRoot = dirname(ThemeConvention::appCssPath($id));
        $cssPath = ThemeConvention::appCssPath($id);

        if (File::isDirectory($themeRoot) && ! $force) {
            return new SubThemeScaffoldResult(
                false,
                $id,
                "Theme directory already exists: {$themeRoot}",
            );
        }

        File::ensureDirectoryExists($themeRoot);

        $replacements = [
            '{{ id }}' => $id,
            '{{ name }}' => $label,
        ];

        self::writeStub('theme-doc.css.stub', $cssPath, $replacements, $force);

        $definition = [
            'label' => $label,
            'description' => "Custom {$label} documentation theme.",
            'type' => 'content',
            'capabilities' => ['doc'],
            'css' => ThemeConvention::appCssRelativePath($id),
        ];

        $configRegistered = ConfigureSubThemesForVoodbuilder::registerInConfig($id, $definition);
        $importAppended = AppendThemeStylesheetImport::append($cssPath);
        SyncThemeStylesheetImports::sync();

        app(SubThemeRegistry::class)->register($id, $definition);

        return new SubThemeScaffoldResult(
            success: true,
            id: $id,
            configRegistered: $configRegistered,
            importAppended: $importAppended,
            cssPath: $cssPath,
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function createFromDefinition(string $id, string $label, array $definition, bool $force = false): SubThemeScaffoldResult
    {
        $capabilities = is_array($definition['capabilities'] ?? null) ? $definition['capabilities'] : [];
        $isDoc = in_array('doc', $capabilities, true);

        return $isDoc
            ? self::createDoc($id, $label, $force)
            : self::create($id, $label, $force);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private static function writeStub(string $stub, string $destination, array $replacements, bool $force): void
    {
        if (File::exists($destination) && ! $force) {
            return;
        }

        $stubPath = dirname(__DIR__, 2).'/stubs/sub-theme/'.$stub;
        $contents = str_replace(
            array_keys($replacements),
            array_values($replacements),
            File::get($stubPath),
        );

        File::put($destination, $contents);
    }
}
