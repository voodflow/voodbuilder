<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Voodflow\Vpress\Enums\PageBuilder;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsEditorGate;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsPageSaveTest extends TestCase
{
    protected function tearDown(): void
    {
        GrapesJsEditorGate::authorizeUsing(null);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            \Filament\FilamentServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Filament::registerPanel(
            Panel::make()
                ->id('admin')
                ->path('admin'),
        );

        GrapesJsEditorGate::authorizeUsing(
            fn (SitePage $page): bool => $page->usesGrapesJsBuilder() && auth()->check(),
        );
    }

    public function test_admin_can_save_grapesjs_payload(): void
    {
        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }
        };

        $user->forceFill([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes page',
            'slug' => 'grapes-page',
            'builder' => PageBuilder::GrapesJs,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->assertTrue($page->usesGrapesJsBuilder());

        $this->actingAs($user);

        $response = $this->putJson(route('vpress.grapesjs.pages.update', $page), [
            'html' => '<section>Updated</section>',
            'css' => '.updated { color: red; }',
            'project' => ['pages' => []],
        ]);

        $response->assertOk()->assertJson(['saved' => true]);

        $page->refresh();

        $this->assertSame('<section>Updated</section>', $page->builder_payload['html']);
        $this->assertSame('.updated { color: red; }', $page->builder_payload['css']);
    }

    public function test_guest_cannot_save_grapesjs_payload(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Grapes page',
            'slug' => 'grapes-page-guest',
            'builder' => PageBuilder::GrapesJs,
            'published' => true,
        ]);

        $this->putJson(route('vpress.grapesjs.pages.update', $page), [
            'html' => '<section>Nope</section>',
        ])->assertUnauthorized();
    }
}
