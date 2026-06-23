<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ConfigureSubThemesForVpress;
use Voodflow\Vpress\Support\SubThemeManager;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\ThemeConvention;
use Voodflow\Vpress\Tests\TestCase;

class SubThemeManagerTest extends TestCase
{
    protected string $themeId = 'manager-demo';

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(dirname(config_path('vpress.php')));

        if (! is_file(config_path('vpress.php'))) {
            File::put(config_path('vpress.php'), <<<'PHP'
<?php

declare(strict_types=1);

return [
    'sub_themes' => [],
];
PHP);
        }

        $this->seedThemeFiles();
        app(SubThemeRegistry::class)->register($this->themeId, [
            'label' => 'Manager Demo',
            'capabilities' => ['landing'],
            'layouts' => [
                'page' => ThemeConvention::appLayoutView($this->themeId, 'page'),
            ],
            'css' => ThemeConvention::appCssRelativePath($this->themeId),
        ]);

        ConfigureSubThemesForVpress::upsertInConfig($this->themeId, [
            'label' => 'Manager Demo',
            'capabilities' => ['landing'],
            'layouts' => [
                'page' => ThemeConvention::appLayoutView($this->themeId, 'page'),
            ],
            'css' => ThemeConvention::appCssRelativePath($this->themeId),
        ]);
    }

    protected function tearDown(): void
    {
        ConfigureSubThemesForVpress::removeFromConfig($this->themeId);
        File::deleteDirectory(resource_path('vpress/themes/'.$this->themeId));
        File::deleteDirectory(resource_path('views/vpress/themes/'.$this->themeId));

        parent::tearDown();
    }

    public function test_it_renames_an_app_theme_label(): void
    {
        $result = SubThemeManager::updateLabel($this->themeId, 'Renamed Demo');

        $this->assertTrue($result->success);

        /** @var array<string, mixed> $config */
        $config = require config_path('vpress.php');

        $this->assertSame('Renamed Demo', $config['sub_themes'][$this->themeId]['label']);
        $this->assertSame('Renamed Demo', app(SubThemeRegistry::class)->label($this->themeId));
    }

    public function test_it_deletes_an_app_theme_and_clears_settings_references(): void
    {
        VpressSettings::saveData([
            'sub_theme' => $this->themeId,
            'sub_theme_colors' => [
                $this->themeId => [
                    'light' => ['primary' => '#111111'],
                ],
            ],
        ]);

        $result = SubThemeManager::delete($this->themeId, 'site');

        $this->assertTrue($result->success);
        $this->assertFileDoesNotExist(ThemeConvention::appCssPath($this->themeId));
        $this->assertFalse(app(SubThemeRegistry::class)->exists($this->themeId));
        $this->assertSame('site', VpressSettings::get('sub_theme'));
        $this->assertArrayNotHasKey($this->themeId, VpressSettings::get('sub_theme_colors', []));

        /** @var array<string, mixed> $config */
        $config = require config_path('vpress.php');
        $this->assertArrayNotHasKey($this->themeId, $config['sub_themes'] ?? []);
    }

    protected function seedThemeFiles(): void
    {
        $cssPath = ThemeConvention::appCssPath($this->themeId);
        $viewsPath = ThemeConvention::appViewsPath($this->themeId).'/layouts/page.blade.php';

        File::ensureDirectoryExists(dirname($cssPath));
        File::ensureDirectoryExists(dirname($viewsPath));

        File::put($cssPath, "html[data-vpress-sub-theme='{$this->themeId}'] { --demo: 1; }\n");
        File::put($viewsPath, "<div>Manager demo page</div>\n");
    }
}
