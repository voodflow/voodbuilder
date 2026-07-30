<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

final class ConfigureNpmForVoodbuilder
{
    /**
     * @return list<string> Package names that were added to package.json
     */
    public static function apply(bool $force = false): array
    {
        $packageJsonPath = base_path('package.json');

        if (! is_file($packageJsonPath)) {
            return [];
        }

        $package = json_decode((string) file_get_contents($packageJsonPath), true);

        if (! is_array($package)) {
            return [];
        }

        $original = $package;
        $added = [];
        $devDependencies = $package['devDependencies'] ?? [];

        if (! is_array($devDependencies)) {
            $devDependencies = [];
        }

        foreach (self::missingFromPackage($package) as $name) {
            $devDependencies[$name] = self::requiredDevDependencies()[$name];
            $added[] = $name;
        }

        if ($added !== []) {
            ksort($devDependencies);
            $package['devDependencies'] = $devDependencies;
        }

        self::ensureBuildScript($package);

        $encoded = json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
        $originalEncoded = json_encode($original, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

        if ($added === [] && $encoded === $originalEncoded && ! $force) {
            return [];
        }

        File::put($packageJsonPath, $encoded);

        return $added;
    }

    /**
     * @return list<string>
     */
    public static function missingFromPackageJson(?string $packageJsonPath = null): array
    {
        $packageJsonPath ??= base_path('package.json');

        if (! is_file($packageJsonPath)) {
            return array_keys(self::requiredDevDependencies());
        }

        $package = json_decode((string) file_get_contents($packageJsonPath), true);

        if (! is_array($package)) {
            return array_keys(self::requiredDevDependencies());
        }

        return self::missingFromPackage($package);
    }

    /**
     * @param  array<string, mixed>  $package
     * @return list<string>
     */
    public static function missingFromPackage(array $package): array
    {
        $dependencies = is_array($package['dependencies'] ?? null) ? $package['dependencies'] : [];
        $devDependencies = is_array($package['devDependencies'] ?? null) ? $package['devDependencies'] : [];
        $missing = [];

        foreach (self::requiredDevDependencies() as $name => $version) {
            if (array_key_exists($name, $devDependencies) || array_key_exists($name, $dependencies)) {
                continue;
            }

            $missing[] = $name;
        }

        return $missing;
    }

    /**
     * @return array<string, string>
     */
    public static function requiredDevDependencies(): array
    {
        return [
            '@fontsource-variable/inter' => '^5.2.8',
            '@fontsource/jetbrains-mono' => '^5.2.8',
            '@jodit/image-editor' => '^0.2.5',
            '@tabler/icons' => '^3.45.0',
            '@tailwindcss/vite' => '^4.3.0',
            'grapesjs' => '^0.23.2',
            'grapesjs-blocks-basic' => '^1.0.2',
            'grapesjs-custom-code' => '^1.0.2',
            'grapesjs-plugin-forms' => '^2.0.6',
            'grapesjs-style-bg' => '^2.0.2',
            'grapesjs-tabs' => '^1.0.6',
            'grapesjs-tailwindcss-plugin' => '^0.1.10',
            'tailwindcss' => '^4.3.0',
            'tailwindcss-animated' => '^2.0.0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sectionSourceDevDependencies(): array
    {
        return [
            'esbuild' => '^0.25.0',
            'prop-types' => '^15.8.1',
            'react' => '^19.0.0',
            'react-dom' => '^19.0.0',
        ];
    }

    /**
     * @param  array<string, mixed>  $package
     */
    protected static function ensureBuildScript(array &$package): void
    {
        $scripts = $package['scripts'] ?? [];

        if (! is_array($scripts)) {
            $scripts = [];
        }

        // Tabler catalog is built by the Vite plugin (vite.config.js); avoid running the
        // Node script twice during `npm run build`.
        $preferredBuild = 'php artisan voodbuilder:sync-theme-imports && vite build';
        $build = $scripts['build'] ?? null;

        if (! is_string($build) || $build === '' || $build === 'vite build') {
            $scripts['build'] = $preferredBuild;
            $package['scripts'] = $scripts;

            return;
        }

        if (str_contains($build, 'build-tabler-icons-catalog') && str_contains($build, 'vite build')) {
            $scripts['build'] = preg_replace(
                '#(?:^|&&\s*)node\s+packages/voodflow/voodbuilder/bin/build-tabler-icons-catalog\.js\s*(?:&&\s*)?#',
                '',
                $build,
            ) ?? $build;
            $scripts['build'] = trim(preg_replace('#\s*&&\s*&&\s*#', ' && ', $scripts['build']) ?? $scripts['build']);

            if ($scripts['build'] === '' || $scripts['build'] === 'vite build') {
                $scripts['build'] = $preferredBuild;
            }

            $package['scripts'] = $scripts;
        }
    }
}
