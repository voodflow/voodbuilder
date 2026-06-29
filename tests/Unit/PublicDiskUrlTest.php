<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\PublicDiskUrl;
use Voodflow\Voodbuilder\Tests\TestCase;

class PublicDiskUrlTest extends TestCase
{
    public function test_builds_relative_storage_path(): void
    {
        $this->assertSame(
            '/storage/voodbuilder/grapesjs/photo.jpg',
            PublicDiskUrl::fromPath('voodbuilder/grapesjs/photo.jpg'),
        );
    }
}
