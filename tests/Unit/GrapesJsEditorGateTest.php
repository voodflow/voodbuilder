<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Http\Request;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsEditorGateTest extends TestCase
{
    protected function tearDown(): void
    {
        GrapesJsEditorGate::authorizeUsing(null);

        parent::tearDown();
    }

    public function test_admin_preview_does_not_enable_editor_without_edit_query(): void
    {
        GrapesJsEditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesGrapesJsBuilder(),
        );

        $page = new SitePage([
            'builder' => PageBuilder::GrapesJs,
        ]);

        $this->app->instance('request', Request::create('/pages/landing-page', 'GET'));

        $this->assertTrue(GrapesJsEditorGate::canEdit($page));
        $this->assertFalse(GrapesJsEditorGate::isEditing($page));
    }

    public function test_edit_query_enables_editor_for_authorized_users(): void
    {
        GrapesJsEditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesGrapesJsBuilder(),
        );

        $page = SitePage::query()->create([
            'title' => 'Landing',
            'slug' => 'landing-gate-test',
            'builder' => PageBuilder::GrapesJs,
            'published' => true,
            'builder_payload' => [
                'html' => '<section>Hero</section>',
                'css' => '',
            ],
        ]);

        $this->app->instance('request', Request::create('/pages/landing-page?edit=1', 'GET'));

        $this->assertTrue(GrapesJsEditorGate::isEditing($page));

        $config = GrapesJsEditorGate::config($page);

        $this->assertSame('<section class="voodbuilder-gjs-section bg-vp-bg">Hero</section>', $config['initial']['html']);
        $this->assertArrayHasKey('pageManager', $config['initial']);
        $this->assertArrayHasKey('blocksUrl', $config);
        $this->assertArrayNotHasKey('blocks', $config);
    }
}
