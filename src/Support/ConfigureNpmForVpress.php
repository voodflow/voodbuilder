<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class ConfigureNpmForVpress
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

        foreach (self::requiredDevDependencies() as $name => $version) {
            if (array_key_exists($name, $devDependencies) || array_key_exists($name, $package['dependencies'] ?? [])) {
                continue;
            }

            $devDependencies[$name] = $version;
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
     * @return array<string, string>
     */
    public static function requiredDevDependencies(): array
    {
        return [
            '@fontsource-variable/inter' => '^5.2.8',
            '@fontsource/jetbrains-mono' => '^5.2.8',
            '@tailwindcss/vite' => '^4.3.0',
            'grapesjs' => '^0.23.2',
            'grapesjs-blocks-basic' => '^1.0.2',
            'tailwindcss' => '^4.3.0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tailblocksDevDependencies(): array
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

        $build = $scripts['build'] ?? null;

        if (! is_string($build) || $build === '') {
            $scripts['build'] = 'php artisan vpress:sync-theme-imports && vite build';
            $package['scripts'] = $scripts;

            return;
        }

        if (str_contains($build, 'vpress:sync-theme-imports') || ! str_contains($build, 'vite build')) {
            return;
        }

        if ($build === 'vite build') {
            $scripts['build'] = 'php artisan vpress:sync-theme-imports && vite build';
            $package['scripts'] = $scripts;
        }
    }
}
