<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsBindingsPreviewController;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationListResolver;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRelationFilters;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationSortFields;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;

class ModelIntegrationRelationFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('filter_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('filter_tutorials', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->foreignId('category_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_builds_belongs_to_filter_options(): void
    {
        FilterCategory::query()->create(['name' => 'Filament']);
        FilterCategory::query()->create(['name' => 'Laravel']);

        $integration = new ModelIntegration([
            'name' => 'Tutorials',
            'model_class' => FilterTutorial::class,
            'model_alias' => 'vtut',
            'fields' => [
                'essential' => ['title'],
                'relations' => [
                    [
                        'name' => 'category',
                        'alias' => 'category',
                        'fields' => ['name'],
                    ],
                ],
            ],
        ]);

        $filters = (new ModelIntegrationRelationFilters)->forIntegration($integration);

        $this->assertCount(1, $filters);
        $this->assertSame('category', $filters[0]['id']);
        $this->assertSame('category_id', $filters[0]['foreign_key']);
        $this->assertSame([
            ['value' => '1', 'label' => 'Filament'],
            ['value' => '2', 'label' => 'Laravel'],
        ], $filters[0]['options']);
    }

    public function test_list_resolver_applies_relation_filter(): void
    {
        $filament = FilterCategory::query()->create(['name' => 'Filament']);
        $laravel = FilterCategory::query()->create(['name' => 'Laravel']);

        FilterTutorial::query()->create(['title' => 'A', 'category_id' => $filament->id]);
        FilterTutorial::query()->create(['title' => 'B', 'category_id' => $laravel->id]);
        FilterTutorial::query()->create(['title' => 'C', 'category_id' => $filament->id]);

        $integration = new ModelIntegration([
            'name' => 'Tutorials',
            'model_class' => FilterTutorial::class,
            'model_alias' => 'vtut',
            'fields' => [
                'essential' => ['title'],
                'relations' => [
                    [
                        'name' => 'category',
                        'alias' => 'category',
                        'fields' => ['name'],
                    ],
                ],
            ],
        ]);

        $registry = new ModelIntegrationRegistry;
        $registry->register($integration);

        $resolver = new ModelIntegrationListResolver(
            $registry,
            new ModelIntegrationSortFields,
            new RepeatListRegistry,
        );

        $records = $resolver->resolve(
            'vtut.list',
            limit: 10,
            sort: 'id',
            direction: 'asc',
            offset: 0,
            filters: ['category' => (string) $filament->id],
        );

        $this->assertCount(2, $records);
        $this->assertSame(['A', 'C'], array_map(
            static fn (FilterTutorial $tutorial): string => $tutorial->title,
            $records,
        ));
    }

    public function test_list_values_key_includes_sorted_filters(): void
    {
        $this->assertSame(
            'vtut.list|id|desc|0',
            GrapesJsBindingsPreviewController::listValuesKey('vtut.list', 'id', 'desc', 0),
        );

        $this->assertSame(
            'vtut.list|id|desc|1|category:3',
            GrapesJsBindingsPreviewController::listValuesKey('vtut.list', 'id', 'desc', 1, [
                'category' => '3',
            ]),
        );

        $this->assertSame(
            'vtut.list|id|desc|0|author:2,category:3',
            GrapesJsBindingsPreviewController::listValuesKey('vtut.list', 'id', 'desc', 0, [
                'category' => '3',
                'author' => '2',
            ]),
        );
    }
}

class FilterCategory extends Model
{
    protected $table = 'filter_categories';

    protected $guarded = [];
}

class FilterTutorial extends Model
{
    protected $table = 'filter_tutorials';

    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FilterCategory::class, 'category_id');
    }
}
