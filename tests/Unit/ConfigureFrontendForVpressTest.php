<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Vpress\Support\ConfigureNpmForVpress;
use Voodflow\Vpress\Support\ConfigureViteForVpress;
use Voodflow\Vpress\Support\VpressPaths;
use Voodflow\Vpress\Tests\TestCase;

class ConfigureFrontendForVpressTest extends TestCase
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

        $added = ConfigureNpmForVpress::apply();

        $this->assertContains('grapesjs', $added);
        $this->assertContains('tailwindcss', $added);

        $package = json_decode((string) file_get_contents(base_path('package.json')), true);

        $this->assertArrayHasKey('grapesjs', $package['devDependencies']);
        $this->assertStringContainsString('vpress:sync-theme-imports', $package['scripts']['build']);
    }

    public function test_configure_npm_skips_existing_packages(): void
    {
        File::put(base_path('package.json'), json_encode([
            'private' => true,
            'devDependencies' => array_merge(
                ['vite' => '^8.0.0'],
                ConfigureNpmForVpress::requiredDevDependencies(),
            ),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $added = ConfigureNpmForVpress::apply();

        $this->assertSame([], $added);
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

        $updated = ConfigureViteForVpress::apply();

        $this->assertTrue($updated);

        $contents = (string) file_get_contents(base_path('vite.config.js'));

        foreach (VpressPaths::viteInputEntries() as $entry) {
            $this->assertStringContainsString($entry, $contents);
        }
    }
}
