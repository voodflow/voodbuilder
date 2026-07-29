<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingUrlResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class BindingUrlResolverTest extends TestCase
{
    public function test_slug_field_resolves_to_model_get_url(): void
    {
        $record = new SlugLinkRecord([
            'slug' => 'filament-admin-panel',
        ]);

        $this->assertSame(
            '/tutorials/series/filament-admin-panel',
            BindingUrlResolver::resolve($record, 'slug'),
        );
    }

    public function test_normalizes_absolute_urls_to_relative_paths(): void
    {
        $this->assertSame(
            '/tutorials/series/demo',
            BindingUrlResolver::normalize('http://localhost:8006/tutorials/series/demo'),
        );
    }
}

class SlugLinkRecord extends Model
{
    protected $guarded = [];

    public function getUrl(): string
    {
        return '/tutorials/series/'.$this->slug;
    }
}
