<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit\Editor\Media;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\Editor\Media\MediaCollectionConfig;
use Voodflow\Voodbuilder\Tests\TestCase;

final class MediaCollectionConfigTest extends TestCase
{
    #[Test]
    public function it_normalizes_multiple_collections(): void
    {
        $config = MediaCollectionConfig::normalize([
            'collections' => ['gallery', 'logo', 'gallery'],
        ]);

        $this->assertSame(['gallery', 'logo'], $config['collections']);
        $this->assertSame('gallery', $config['collection']);
    }

    #[Test]
    public function it_migrates_legacy_single_collection_key(): void
    {
        $config = MediaCollectionConfig::normalize([
            'collection' => 'attachments',
        ]);

        $this->assertSame(['attachments'], $config['collections']);
    }
}
