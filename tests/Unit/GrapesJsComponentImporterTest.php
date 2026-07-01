<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Models\BuilderComponent;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentImporter;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsComponentImporterTest extends TestCase
{
    public function test_imports_valid_components(): void
    {
        $importer = app(GrapesJsComponentImporter::class);

        $created = $importer->import([
            [
                'name' => 'Hero',
                'category' => 'hero',
                'html' => '<section class="hero"><h1>Hello</h1></section>',
                'properties' => [
                    ['id' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Hello'],
                ],
            ],
            [
                'name' => 'CTA',
                'html' => '<a href="#">Click</a>',
            ],
        ]);

        $this->assertCount(2, $created);
        $this->assertSame('Hero', $created[0]->name);
        $this->assertSame('Hero', $created[0]->category);
        $this->assertDatabaseHas('voodbuilder_components', ['name' => 'CTA']);
        $this->assertSame(2, BuilderComponent::query()->count());
    }

    public function test_rejects_component_without_name(): void
    {
        $this->expectException(ValidationException::class);

        app(GrapesJsComponentImporter::class)->import([
            ['html' => '<p>Missing name</p>'],
        ]);
    }
}
