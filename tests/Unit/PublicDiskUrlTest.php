<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\PublicDiskUrl;
use Voodflow\Vpress\Tests\TestCase;

class PublicDiskUrlTest extends TestCase
{
    public function test_builds_relative_storage_path(): void
    {
        $this->assertSame(
            '/storage/vpress/grapesjs/photo.jpg',
            PublicDiskUrl::fromPath('vpress/grapesjs/photo.jpg'),
        );
    }
}
