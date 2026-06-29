<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Voodflow\Voodbuilder\Support\SubThemeExporter;
use Voodflow\Voodbuilder\Support\SubThemeImporter;
use Voodflow\Voodbuilder\Support\SubThemeLocator;
use Voodflow\Voodbuilder\Support\ThemeConvention;
use Voodflow\Voodbuilder\Tests\TestCase;

class SubThemeExporterImporterTest extends TestCase
{
    protected string $themeId = 'export-demo';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedThemeFiles();
        app(\Voodflow\Voodbuilder\Support\SubThemeRegistry::class)->register($this->themeId, [
            'label' => 'Export Demo',
            'capabilities' => ['landing'],
            'layouts' => [
                'page' => ThemeConvention::appLayoutView($this->themeId, 'page'),
            ],
            'css' => ThemeConvention::appCssRelativePath($this->themeId),
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(resource_path('voodbuilder/themes/'.$this->themeId));
        File::deleteDirectory(resource_path('views/voodbuilder/themes/'.$this->themeId));

        parent::tearDown();
    }

    public function test_it_resolves_archive_path_on_local_private_disk(): void
    {
        Storage::fake('local');

        $relativePath = 'voodbuilder-theme-imports/demo.zip';
        Storage::disk('local')->put($relativePath, 'zip-content');

        $resolved = \Voodflow\Voodbuilder\Support\SubThemeImporter::resolveArchiveUploadPath($relativePath);

        $this->assertSame(Storage::disk('local')->path($relativePath), $resolved);
        $this->assertFileExists($resolved);
    }

    public function test_it_renames_theme_on_import_when_id_already_exists(): void
    {
        $exportPath = storage_path('app/voodbuilder-theme-exports/'.$this->themeId.'-rename-test.zip');
        SubThemeExporter::export($this->themeId, $exportPath);

        $result = \Voodflow\Voodbuilder\Support\SubThemeImporter::import(
            archivePath: $exportPath,
            renameOnConflict: true,
        );

        $this->assertTrue($result->success);
        $this->assertSame($this->themeId, $result->renamedFrom);
        $this->assertSame($this->themeId.'-copy', $result->id);
        $this->assertFileExists(ThemeConvention::appCssPath($result->id));

        File::deleteDirectory(resource_path('voodbuilder/themes/'.$result->id));
        File::deleteDirectory(resource_path('views/voodbuilder/themes/'.$result->id));
        File::delete($exportPath);
    }

    public function test_it_clones_a_theme_with_new_id(): void
    {
        $result = \Voodflow\Voodbuilder\Support\SubThemeCloner::clone(
            sourceId: $this->themeId,
            targetId: 'cloned-demo',
            label: 'Cloned Demo',
        );

        $this->assertTrue($result->success);
        $this->assertSame('cloned-demo', $result->id);
        $this->assertFileExists(ThemeConvention::appCssPath('cloned-demo'));
        $this->assertStringContainsString(
            "data-voodbuilder-sub-theme='cloned-demo'",
            File::get(ThemeConvention::appCssPath('cloned-demo')),
        );

        File::deleteDirectory(resource_path('voodbuilder/themes/cloned-demo'));
        File::deleteDirectory(resource_path('views/voodbuilder/themes/cloned-demo'));
    }

    public function test_it_exports_and_imports_a_theme_archive(): void
    {
        $exportPath = storage_path('app/voodbuilder-theme-exports/'.$this->themeId.'-test.zip');
        SubThemeExporter::export($this->themeId, $exportPath);

        $this->assertFileExists($exportPath);

        $importId = 'imported-demo';
        $result = SubThemeImporter::import(
            archivePath: $exportPath,
            force: true,
            importColors: false,
            targetId: $importId,
        );

        $this->assertTrue($result->success);
        $this->assertFileExists(ThemeConvention::appCssPath($importId));
        $this->assertFileExists(ThemeConvention::appViewsPath($importId).'/layouts/page.blade.php');
        $this->assertNotNull(SubThemeLocator::resolve($importId));

        File::deleteDirectory(resource_path('voodbuilder/themes/'.$importId));
        File::deleteDirectory(resource_path('views/voodbuilder/themes/'.$importId));
        File::delete($exportPath);
    }

    protected function seedThemeFiles(): void
    {
        $cssPath = ThemeConvention::appCssPath($this->themeId);
        $viewsPath = ThemeConvention::appViewsPath($this->themeId).'/layouts/page.blade.php';

        File::ensureDirectoryExists(dirname($cssPath));
        File::ensureDirectoryExists(dirname($viewsPath));

        File::put($cssPath, "html[data-voodbuilder-sub-theme='{$this->themeId}'] { --demo: 1; }\n");
        File::put($viewsPath, "<div>Export demo page</div>\n");
    }
}
