<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPagePreviewEntityResolver;

class DynamicPagePreviewEntityResolverTest extends TestCase
{
    public function test_builds_slug_bag_from_models(): void
    {
        $model = new class extends Model
        {
            protected $table = 'preview_models';
        };
        $model->setAttribute('slug', 'yamaha');

        $this->assertSame(
            ['exhibitor' => 'yamaha'],
            DynamicPagePreviewEntityResolver::slugBagFromModels(['exhibitor' => $model]),
        );
    }
}
