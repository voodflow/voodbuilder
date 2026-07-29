<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use ReflectionMethod;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Tests\TestCase;

class NavigationMenuResourceTest extends TestCase
{
    public function test_site_page_options_lists_only_editor_pages(): void
    {
        SitePage::query()->create([
            'title' => 'Rich page',
            'slug' => 'rich-page',
            'builder' => PageBuilder::RichEditor,
            'published' => true,
        ]);

        SitePage::query()->create([
            'title' => 'Grapes page',
            'slug' => 'grapes-page',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $method = new ReflectionMethod(NavigationMenuResource::class, 'sitePageOptions');
        $options = $method->invoke(null);

        $this->assertCount(1, $options);
        $this->assertArrayHasKey('grapes-page', $options);
        $this->assertStringContainsString('Grapes page', $options['grapes-page']);
        $this->assertArrayNotHasKey('rich-page', $options);
    }
}
