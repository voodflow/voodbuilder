<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\PageCssArtifactStore;
use Voodflow\Voodbuilder\Tests\TestCase;

class PageCssArtifactStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'voodbuilder.editor.payload.css_artifact_threshold_bytes' => 100,
            'voodbuilder.editor.payload.css_artifact_disk' => 'public',
            'voodbuilder.editor.payload.css_artifact_directory' => 'voodbuilder/page-css',
        ]);
    }

    public function test_small_css_stays_inline_without_artifact(): void
    {
        $page = $this->makePage();
        $css = '.hero{color:red}';

        $stored = PageCssArtifactStore::persistForPage($page, $css, '#hero{color:red}');

        $this->assertSame($css, $stored['css']);
        $this->assertNull($stored[PageCssArtifactStore::META_KEY]);
        $this->assertFalse(Storage::disk('public')->exists('voodbuilder/page-css/page-'.$page->id.'.css'));
    }

    public function test_large_css_writes_artifact_and_keeps_author_inline(): void
    {
        $page = $this->makePage();
        $author = '#hero{color:red}';
        $full = $author."\n".str_repeat('.u'.str_repeat('x', 20).'{display:flex}', 20);

        $this->assertGreaterThan(100, strlen($full));

        $stored = PageCssArtifactStore::persistForPage($page, $full, $author);

        $this->assertSame($author, $stored['css']);
        $this->assertNotNull($stored[PageCssArtifactStore::META_KEY]);
        $this->assertSame(
            'voodbuilder/page-css/page-'.$page->id.'.css',
            $stored[PageCssArtifactStore::META_KEY]['path'],
        );
        $this->assertSame($full, Storage::disk('public')->get($stored[PageCssArtifactStore::META_KEY]['path']));

        $payload = [
            'html' => '<section class="hero">x</section>',
            'css' => $stored['css'],
            PageCssArtifactStore::META_KEY => $stored[PageCssArtifactStore::META_KEY],
        ];

        $this->assertSame($full, PageCssArtifactStore::resolveCss($payload));
        $this->assertStringStartsWith('/storage/voodbuilder/page-css/page-', (string) PageCssArtifactStore::publicUrl($payload));
    }

    public function test_slim_payload_for_history_extracts_large_inline_css(): void
    {
        $large = str_repeat('.box{display:flex}', 30);
        $this->assertGreaterThan(100, strlen($large));

        $slimmed = PageCssArtifactStore::slimPayloadForHistory(
            ['html' => '<div></div>', 'css' => $large, 'js' => ''],
            42,
            'prev-test',
        );

        $this->assertLessThan(strlen($large), strlen((string) $slimmed['css']));
        $this->assertNotNull($slimmed[PageCssArtifactStore::META_KEY] ?? null);
        $this->assertSame(
            $large,
            Storage::disk('public')->get($slimmed[PageCssArtifactStore::META_KEY]['path']),
        );
    }

    private function makePage(): SitePage
    {
        return SitePage::query()->create([
            'title' => 'Artifact page',
            'slug' => 'artifact-'.uniqid(),
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);
    }
}
