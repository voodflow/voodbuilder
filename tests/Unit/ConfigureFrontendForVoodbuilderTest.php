<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Voodbuilder\Support\ConfigureNpmForVoodbuilder;
use Voodflow\Voodbuilder\Support\ConfigureViteForVoodbuilder;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
use Voodflow\Voodbuilder\Tests\TestCase;

class ConfigureFrontendForVoodbuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        $packageJson = base_path('package.json');
        $viteConfig = base_path('vite.config.js');

        if (is_file($packageJson)) {
            unlink($packageJson);
        }

        if (is_file($viteConfig)) {
            unlink($viteConfig);
        }

        parent::tearDown();
    }

    public function test_configure_npm_adds_required_dev_dependencies(): void
    {
        File::put(base_path('package.json'), json_encode([
            'private' => true,
            'scripts' => [
                'build' => 'vite build',
            ],
            'devDependencies' => [
                'vite' => '^8.0.0',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $added = ConfigureNpmForVoodbuilder::apply();

        $this->assertContains('grapesjs', $added);
        $this->assertContains('grapesjs-tailwindcss-plugin', $added);
        $this->assertContains('tailwindcss', $added);

        $package = json_decode((string) file_get_contents(base_path('package.json')), true);

        $this->assertArrayHasKey('grapesjs', $package['devDependencies']);
        $this->assertStringContainsString('voodbuilder:sync-theme-imports', $package['scripts']['build']);
    }

    public function test_configure_npm_skips_existing_packages(): void
    {
        File::put(base_path('package.json'), json_encode([
            'private' => true,
            'devDependencies' => array_merge(
                ['vite' => '^8.0.0'],
                ConfigureNpmForVoodbuilder::requiredDevDependencies(),
            ),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $added = ConfigureNpmForVoodbuilder::apply();

        $this->assertSame([], $added);
    }

    public function test_required_dev_dependencies_include_editor_tailwind_plugin(): void
    {
        $this->assertArrayHasKey(
            'grapesjs-tailwindcss-plugin',
            ConfigureNpmForVoodbuilder::requiredDevDependencies(),
        );
    }

    public function test_missing_from_package_json_detects_absent_dependencies(): void
    {
        File::put(base_path('package.json'), json_encode([
            'private' => true,
            'devDependencies' => [
                'vite' => '^8.0.0',
                'grapesjs' => '^0.22.12',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $missing = ConfigureNpmForVoodbuilder::missingFromPackageJson();

        $this->assertContains('grapesjs-tailwindcss-plugin', $missing);
        $this->assertNotContains('grapesjs', $missing);
    }

    public function test_configure_vite_appends_package_entries(): void
    {
        File::put(base_path('vite.config.js'), <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
    ],
});
JS);

        $updated = ConfigureViteForVoodbuilder::apply();

        $this->assertTrue($updated);

        $contents = (string) file_get_contents(base_path('vite.config.js'));

        foreach (VoodbuilderPaths::viteInputEntries() as $entry) {
            $this->assertStringContainsString($entry, $contents);
        }
    }
}
