<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorPlaceholderNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorPlaceholderNormalizerTest extends TestCase
{
    public function test_normalize_data_image_src_encodes_literal_spaces_for_chrome(): void
    {
        $html = '<img src="data:image/svg+xml,%3Csvg%3E%3EImage 1%3C/svg%3E" alt="gallery" />';

        $normalized = EditorPlaceholderNormalizer::normalizeDataImageSrc($html);

        $this->assertStringNotContainsString('Image 1', $normalized);
        $this->assertStringContainsString('Image%201', $normalized);
    }
}
