<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorAssets;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorAssetsTest extends TestCase
{
    public function test_editor_page_vite_entries_exclude_site_runtime(): void
    {
        $entries = EditorAssets::editorPageViteEntries();

        $this->assertSame(VoodbuilderPaths::themeCssRelativePath(), $entries[0]);
        $this->assertContains(VoodbuilderPaths::editorViteEntry(), $entries);
        $this->assertContains(VoodbuilderPaths::editorCssEntry(), $entries);
        $this->assertNotContains('resources/js/app.js', $entries);
        $this->assertFalse(collect($entries)->contains(
            fn (string $entry): bool => str_ends_with($entry, 'site-runtime.js'),
        ));
    }

    public function test_chrome_layout_editor_vite_entries_exclude_block_preview_shim(): void
    {
        $entries = EditorAssets::editorPageViteEntries(chromeLayoutEditor: true);

        $this->assertNotContains(VoodbuilderPaths::editorBlockPreviewCssEntry(), $entries);
        $this->assertContains(VoodbuilderPaths::editorViteEntry(), $entries);
    }

    public function test_page_vite_entries_switch_for_editor(): void
    {
        $public = EditorAssets::pageViteEntries(false);
        $editor = EditorAssets::pageViteEntries(true);

        $this->assertContains(VoodbuilderPaths::themeCssRelativePath(), $public);
        $this->assertContains(VoodbuilderPaths::editorTabsCssEntry(), $public);
        $this->assertContains(VoodbuilderPaths::editorFormsCssEntry(), $public);
        $this->assertTrue(collect($public)->contains(
            fn (string $entry): bool => str_ends_with($entry, 'site-runtime.js'),
        ));
        $this->assertContains(VoodbuilderPaths::editorViteEntry(), $editor);
        $this->assertFalse(collect($editor)->contains(
            fn (string $entry): bool => str_ends_with($entry, 'site-runtime.js'),
        ));
    }

    public function test_page_vite_entries_include_active_sub_theme_css(): void
    {
        $this->assertNull(EditorAssets::subThemeCssViteEntry('site'));

        $landing = EditorAssets::subThemeCssViteEntry('landing-fra');

        if ($landing !== null) {
            $public = EditorAssets::pageViteEntries(false, false, 'landing-fra');
            $this->assertContains($landing, $public);
        }

        $blog = EditorAssets::subThemeCssViteEntry('blog');
        $this->assertNotNull($blog);
        $this->assertStringEndsWith('themes/blog/theme.css', $blog);
    }
}
