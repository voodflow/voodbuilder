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

    public function test_page_vite_entries_skip_runtime_app_sub_theme_css(): void
    {
        $this->assertNull(EditorAssets::subThemeCssViteEntry('site'));

        // App themes under resources/voodbuilder/themes are runtime skins, not Vite inputs.
        $landing = EditorAssets::subThemeCssViteEntry('landing-fra');
        $this->assertNull($landing);

        $public = EditorAssets::pageViteEntries(false, false, 'landing-fra');
        $this->assertFalse(collect($public)->contains(
            fn (string $entry): bool => str_contains($entry, 'resources/voodbuilder/themes/'),
        ));
        $this->assertFalse(collect($public)->contains(
            fn (string $entry): bool => str_contains($entry, 'themes/blog/') || str_contains($entry, 'themes/news/'),
        ));

        // Removed package article skins are not Vite entries either.
        $this->assertNull(EditorAssets::subThemeCssViteEntry('blog'));
        $this->assertNull(EditorAssets::subThemeCssViteEntry('news'));
    }
}
