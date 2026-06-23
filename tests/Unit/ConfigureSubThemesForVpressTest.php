<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Vpress\Support\ConfigureSubThemesForVpress;
use Voodflow\Vpress\Tests\TestCase;

class ConfigureSubThemesForVpressTest extends TestCase
{
    protected string $configPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configPath = config_path('vpress.php');
        File::ensureDirectoryExists(dirname($this->configPath));
    }

    protected function tearDown(): void
    {
        if (is_file($this->configPath)) {
            unlink($this->configPath);
        }

        parent::tearDown();
    }

    public function test_it_upserts_sibling_entries_without_nesting_inside_existing_theme(): void
    {
        File::put($this->configPath, $this->corruptedConfigContents());

        $registered = ConfigureSubThemesForVpress::upsertInConfig('polito2', [
            'label' => 'Polito BIS',
            'description' => 'Clone',
            'capabilities' => ['landing'],
            'layouts' => [
                'home' => 'vpress.themes.polito2.layouts.home',
                'landing' => 'vpress.themes.polito2.layouts.landing',
                'page' => 'vpress.themes.polito2.layouts.page',
            ],
            'css' => 'resources/vpress/themes/polito2/theme.css',
        ]);

        $this->assertTrue($registered);

        /** @var array<string, mixed> $config */
        $config = require $this->configPath;
        $subThemes = $config['sub_themes'];

        $this->assertArrayHasKey('polito', $subThemes);
        $this->assertArrayHasKey('polito2', $subThemes);
        $this->assertArrayNotHasKey('polito2', $subThemes['polito']['layouts']);
        $this->assertSame('Polito BIS', $subThemes['polito2']['label']);
    }

    public function test_register_in_config_skips_existing_theme_ids(): void
    {
        File::put($this->configPath, $this->validConfigContents());

        $registered = ConfigureSubThemesForVpress::registerInConfig('site', [
            'label' => 'Duplicate Site',
            'capabilities' => ['landing'],
        ]);

        $this->assertFalse($registered);

        /** @var array<string, mixed> $config */
        $config = require $this->configPath;
        $this->assertSame('Site', $config['sub_themes']['site']['label']);
    }

    public function test_remove_from_config_deletes_a_theme_entry(): void
    {
        File::put($this->configPath, $this->validConfigContents());

        $removed = ConfigureSubThemesForVpress::removeFromConfig('site');

        $this->assertTrue($removed);

        /** @var array<string, mixed> $config */
        $config = require $this->configPath;
        $this->assertArrayNotHasKey('site', $config['sub_themes']);
    }

    protected function corruptedConfigContents(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'sub_themes' => [
        'polito' => [
            'label' => 'Politecnico di Torino',
            'description' => 'Custom Politecnico di Torino theme.',
            'type' => 'marketing',
            'capabilities' => ['landing'],
            'layouts' => [
                'home' => 'vpress.themes.polito.layouts.home',
                'landing' => 'vpress.themes.polito.layouts.landing',
                'page' => 'vpress.themes.polito.layouts.page',
            ],
            'css' => 'resources/vpress/themes/polito/theme.css',
        ],
    ],
];
PHP;
    }

    protected function validConfigContents(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'sub_themes' => [
        'site' => [
            'label' => 'Site',
            'description' => 'Site theme.',
            'type' => 'marketing',
            'capabilities' => ['landing'],
            'layouts' => [
                'home' => 'vpress.themes.site.layouts.home',
                'landing' => 'vpress.themes.site.layouts.landing',
                'page' => 'vpress.themes.site.layouts.page',
            ],
            'css' => 'resources/vpress/themes/site/theme.css',
        ],
    ],
];
PHP;
    }
}
