<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingMediaUrlResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class BindingMediaUrlResolverTest extends TestCase
{
    public function test_normalizes_absolute_storage_urls_to_relative_paths(): void
    {
        $this->assertSame(
            '/storage/tutorial-series/example.jpg',
            BindingMediaUrlResolver::normalizeForEditor('https://example.test/storage/tutorial-series/example.jpg'),
        );
    }

    public function test_normalizes_bare_paths_on_public_disk(): void
    {
        $this->assertSame(
            '/storage/uploads/cover.webp',
            BindingMediaUrlResolver::normalizeForEditor('uploads/cover.webp'),
        );
    }

    public function test_preserves_existing_relative_storage_paths(): void
    {
        $this->assertSame(
            '/storage/media/featured.jpg',
            BindingMediaUrlResolver::normalizeForEditor('/storage/media/featured.jpg'),
        );
    }

    public function test_uses_editor_media_proxy_path(): void
    {
        $this->assertSame(
            '/voodbuilder/grapesjs/media/23',
            BindingMediaUrlResolver::editorMediaPreviewPath(23),
        );
    }
}
