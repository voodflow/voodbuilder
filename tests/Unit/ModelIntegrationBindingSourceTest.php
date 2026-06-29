<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationLatestBindingSource;
use Voodflow\Voodbuilder\Tests\TestCase;

class ModelIntegrationBindingSourceTest extends TestCase
{
    public function test_resolves_featured_image_via_url_accessor(): void
    {
        $integration = new ModelIntegration([
            'name' => 'Tutorial Series',
            'model_class' => FeaturedImageSeries::class,
            'fields' => [
                'essential' => ['featured_image'],
            ],
        ]);

        FeaturedImageSeries::$record = new FeaturedImageSeries([
            'featured_image' => 'tutorial-series/example.jpg',
        ]);

        $source = new ModelIntegrationLatestBindingSource($integration);

        $this->assertSame(
            'https://example.test/storage/tutorial-series/example.jpg',
            $source->resolve('featured_image', BindingContext::forPage(null)),
        );
    }

    public function test_resolves_relative_image_paths_on_public_disk(): void
    {
        $integration = new ModelIntegration([
            'name' => 'Gallery Item',
            'model_class' => PlainImageRecord::class,
            'fields' => [
                'essential' => ['cover_image'],
            ],
        ]);

        PlainImageRecord::$record = new PlainImageRecord([
            'cover_image' => 'uploads/cover.webp',
        ]);

        $source = new ModelIntegrationLatestBindingSource($integration);

        $this->assertSame(
            '/storage/uploads/cover.webp',
            $source->resolve('cover_image', BindingContext::forPage(null)),
        );
    }
}

class FeaturedImageSeries extends Model
{
    public static ?self $record = null;

    protected $guarded = [];

    public static function query(): FeaturedImageSeriesQuery
    {
        return new FeaturedImageSeriesQuery;
    }

    public function featuredImageUrl(): string
    {
        return 'https://example.test/storage/'.$this->featured_image;
    }
}

class FeaturedImageSeriesQuery
{
    public function published(): self
    {
        return $this;
    }

    public function latest(string $column): self
    {
        return $this;
    }

    public function first(): ?FeaturedImageSeries
    {
        return FeaturedImageSeries::$record;
    }
}

class PlainImageRecord extends Model
{
    public static ?self $record = null;

    protected $guarded = [];

    public static function query(): PlainImageRecordQuery
    {
        return new PlainImageRecordQuery;
    }
}

class PlainImageRecordQuery
{
    public function published(): self
    {
        return $this;
    }

    public function latest(string $column): self
    {
        return $this;
    }

    public function first(): ?PlainImageRecord
    {
        return PlainImageRecord::$record;
    }
}
