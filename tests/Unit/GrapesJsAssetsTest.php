<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsAssets;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsAssetsTest extends TestCase
{
    public function test_editor_page_vite_entries_exclude_site_runtime(): void
    {
        $entries = GrapesJsAssets::editorPageViteEntries();

        $this->assertSame(VoodbuilderPaths::themeCssRelativePath(), $entries[0]);
        $this->assertContains(VoodbuilderPaths::grapesJsViteEntry(), $entries);
        $this->assertContains(VoodbuilderPaths::grapesJsEditorCssEntry(), $entries);
        $this->assertNotContains('resources/js/app.js', $entries);
        $this->assertFalse(collect($entries)->contains(
            fn (string $entry): bool => str_ends_with($entry, 'site-runtime.js'),
        ));
    }

    public function test_chrome_layout_editor_vite_entries_exclude_block_preview_shim(): void
    {
        $entries = GrapesJsAssets::editorPageViteEntries(chromeLayoutEditor: true);

        $this->assertNotContains(VoodbuilderPaths::grapesJsBlockPreviewCssEntry(), $entries);
        $this->assertContains(VoodbuilderPaths::grapesJsViteEntry(), $entries);
    }

    public function test_page_vite_entries_switch_for_editor(): void
    {
        $public = GrapesJsAssets::pageViteEntries(false);
        $editor = GrapesJsAssets::pageViteEntries(true);

        $this->assertSame(VoodbuilderPaths::defaultViteEntries(), $public);
        $this->assertSame(GrapesJsAssets::editorPageViteEntries(), $editor);
    }
}
