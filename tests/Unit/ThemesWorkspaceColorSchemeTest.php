<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Voodflow\Voodbuilder\Filament\Livewire\ThemesWorkspace;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\ConfigureSubThemesForVoodbuilder;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\ThemeConvention;
use Voodflow\Voodbuilder\Support\ThemePresenter;
use Voodflow\Voodbuilder\Tests\TestCase;

class ThemesWorkspaceColorSchemeTest extends TestCase
{
    protected string $sourceId = 'scheme-source';

    protected string $targetId = 'scheme-target';

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(dirname(config_path('voodbuilder.php')));

        if (! is_file(config_path('voodbuilder.php'))) {
            File::put(config_path('voodbuilder.php'), <<<'PHP'
<?php

declare(strict_types=1);

return [
    'sub_themes' => [],
];
PHP);
        }

        foreach ([$this->sourceId, $this->targetId] as $id) {
            $this->seedThemeFiles($id);
            $definition = [
                'label' => ucfirst(str_replace('-', ' ', $id)),
                'capabilities' => ['landing'],
                'layouts' => [
                    'page' => ThemeConvention::appLayoutView($id, 'page'),
                ],
                'css' => ThemeConvention::appCssRelativePath($id),
            ];
            app(SubThemeRegistry::class)->register($id, $definition);
            ConfigureSubThemesForVoodbuilder::upsertInConfig($id, $definition);
        }

        VoodbuilderSettings::saveData([
            'sub_theme_colors' => [
                $this->sourceId => [
                    'custom' => true,
                    'light' => [
                        'primary' => '#112233',
                        'secondary' => '#445566',
                        'text' => '#abcdef',
                    ],
                    'dark' => [
                        'primary' => '#fedcba',
                        'body_bg' => '#101010',
                    ],
                ],
                $this->targetId => [
                    'custom' => true,
                    'light' => [
                        'primary' => '#000000',
                    ],
                    'dark' => [],
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([$this->sourceId, $this->targetId] as $id) {
            ConfigureSubThemesForVoodbuilder::removeFromConfig($id);
            File::deleteDirectory(resource_path('voodbuilder/themes/'.$id));
            File::deleteDirectory(resource_path('views/voodbuilder/themes/'.$id));
        }

        parent::tearDown();
    }

    public function test_it_copies_and_pastes_color_scheme_between_custom_themes(): void
    {
        $component = Livewire::test(ThemesWorkspace::class)
            ->call('selectTheme', $this->sourceId)
            ->assertSet('canEditColors', true)
            ->call('copyColorScheme')
            ->assertSet('copiedColorScheme.source_id', $this->sourceId)
            ->assertSet('copiedColorScheme.light.primary', '#112233')
            ->assertSet('copiedColorScheme.dark.primary', '#fedcba');

        $this->assertFalse($component->instance()->canPasteColorScheme());

        $component
            ->call('selectTheme', $this->targetId)
            ->assertSet('canEditColors', true);

        $this->assertTrue($component->instance()->canPasteColorScheme());

        $component
            ->call('pasteColorScheme')
            ->assertSet('light.primary', '#112233')
            ->assertSet('light.secondary', '#445566')
            ->assertSet('light.text', '#abcdef')
            ->assertSet('dark.primary', '#fedcba')
            ->assertSet('dark.body_bg', '#101010');

        $this->assertSame('#112233', ThemePresenter::modeColors($this->targetId, 'light')['primary']);
        $this->assertSame('#fedcba', ThemePresenter::modeColors($this->targetId, 'dark')['primary']);
    }

    public function test_it_pastes_color_scheme_from_clipboard_json(): void
    {
        $payload = json_encode([
            'version' => 1,
            'type' => 'voodbuilder-color-scheme',
            'source_id' => $this->sourceId,
            'light' => ['primary' => '#abc123'],
            'dark' => ['primary' => '#321cba'],
        ], JSON_THROW_ON_ERROR);

        Livewire::test(ThemesWorkspace::class)
            ->call('selectTheme', $this->targetId)
            ->call('pasteColorScheme', $payload)
            ->assertSet('light.primary', '#abc123')
            ->assertSet('dark.primary', '#321cba');
    }

    protected function seedThemeFiles(string $themeId): void
    {
        $cssPath = ThemeConvention::appCssPath($themeId);
        $viewsPath = ThemeConvention::appViewsPath($themeId).'/layouts/page.blade.php';

        File::ensureDirectoryExists(dirname($cssPath));
        File::ensureDirectoryExists(dirname($viewsPath));

        File::put($cssPath, "html[data-voodbuilder-sub-theme='{$themeId}'] { --demo: 1; }\n");
        File::put($viewsPath, "<div>{$themeId}</div>\n");
    }
}
