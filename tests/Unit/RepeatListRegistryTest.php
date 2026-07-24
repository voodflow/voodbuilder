<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationListResolver;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationSortFields;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;

class RepeatListRegistryTest extends TestCase
{
    public function test_catalog_and_resolve_with_offset_and_alias(): void
    {
        $registry = new RepeatListRegistry;
        $records = [
            $this->fakeModel(1),
            $this->fakeModel(2),
            $this->fakeModel(3),
            $this->fakeModel(4),
        ];

        $registry->register(
            'demo.list',
            'Demo · Repeat list',
            static function (int $limit, int $offset) use ($records): array {
                return array_slice($records, $offset, $limit);
            },
            [['id' => 'id', 'label' => 'ID']],
            'id',
            'desc',
        );
        $registry->alias('demo.latest_list', 'demo.list');

        $this->assertSame('demo.list', $registry->catalog()[0]['id']);
        $this->assertTrue($registry->has('demo.latest_list'));

        $resolved = $registry->resolve('demo.latest_list', limit: 2, offset: 1);

        $this->assertCount(2, $resolved);
        $this->assertSame(2, $resolved[0]->id);
        $this->assertSame(3, $resolved[1]->id);
    }

    public function test_list_resolver_prefers_package_repeat_lists(): void
    {
        $first = $this->fakeModel(10);
        $second = $this->fakeModel(11);

        $repeatLists = new RepeatListRegistry;
        $repeatLists->register(
            'vtuts.list',
            'Tutorials',
            static function (int $limit, int $offset) use ($first, $second): array {
                return $offset > 0 ? [$second] : [$first];
            },
        );

        $resolver = new ModelIntegrationListResolver(
            new ModelIntegrationRegistry,
            new ModelIntegrationSortFields,
            $repeatLists,
        );

        $records = $resolver->resolve('vtuts.list', limit: 1, offset: 1);

        $this->assertCount(1, $records);
        $this->assertSame(11, $records[0]->id);
    }

    private function fakeModel(int $id): Model
    {
        $model = new class extends Model
        {
            public $incrementing = false;

            protected $guarded = [];
        };

        $model->forceFill(['id' => $id]);
        $model->id = $id;

        return $model;
    }
}
