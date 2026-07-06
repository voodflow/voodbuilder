<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingImageResolverRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingMediaUrlResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class BindingImageResolverRegistryTest extends TestCase
{
    public function test_custom_resolver_overrides_default_image_resolution(): void
    {
        $record = new CustomImageRecord(['title' => 'Custom item']);
        $registry = app(BindingImageResolverRegistry::class);

        $registry->register(CustomImageRecord::class, 'image', static fn (): string => '/cdn/custom-cover.jpg');

        $resolved = BindingMediaUrlResolver::resolve($record, 'image', '/storage/fallback.jpg');

        $this->assertSame('/cdn/custom-cover.jpg', $resolved);
    }
}

class CustomImageRecord extends Model
{
    protected $guarded = [];
}
