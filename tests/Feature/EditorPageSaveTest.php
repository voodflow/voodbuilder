<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorPageSaveTest extends TestCase
{
    protected function tearDown(): void
    {
        EditorGate::authorizeUsing(null);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
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

        EditorGate::authorizeUsing(
            fn (SitePage $page): bool => $page->usesEditorBuilder() && auth()->check(),
        );
    }

    public function test_admin_can_save_editor_payload(): void
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
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->assertTrue($page->usesEditorBuilder());

        $this->actingAs($user);

        $response = $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Updated</section>',
            'css' => '.updated { color: red; }',
            'project' => ['pages' => []],
        ]);

        $response->assertOk()->assertJson(['saved' => true]);

        $page->refresh();

        $this->assertSame('<section class="voodbuilder-editor-section bg-vp-bg">Updated</section>', $page->builder_payload['html']);
        $this->assertStringContainsString('.updated {color: red;}', $page->builder_payload['css']);
        $this->assertStringContainsString('.bg-vp-bg', $page->builder_payload['css']);
        $this->assertStringContainsString('var(--color-vp-bg)', $page->builder_payload['css']);
        $this->assertSame('', $page->builder_payload['js']);
    }

    public function test_admin_can_save_editor_component_scripts(): void
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
            'email' => 'admin-js@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes tabs',
            'slug' => 'grapes-tabs',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->actingAs($user);

        $js = 'var items = document.querySelectorAll("#tabs");';

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<div id="tabs"></div>',
            'css' => '',
            'js' => $js,
        ])->assertOk();

        $page->refresh();

        $this->assertSame($js, $page->builder_payload['js']);
        $this->assertSame($js, $page->renderedScripts());
    }

    public function test_save_migrates_legacy_brand_classes(): void
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
            'email' => 'admin-migrate@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes migrate',
            'slug' => 'grapes-migrate',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<a class="bg-indigo-500 text-white">Button</a>',
            'css' => '.btn { background-color: #6366f1; }',
            'project' => ['pages' => []],
        ])->assertOk();

        $page->refresh();

        $this->assertStringContainsString('bg-indigo-500', $page->builder_payload['html']);
        $this->assertStringContainsString('var(--color-vp-brand-', $page->builder_payload['css']);
    }

    public function test_admin_can_clear_page_html_on_save(): void
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
            'email' => 'admin-clear@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes clear',
            'slug' => 'grapes-clear',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
            'builder_payload' => [
                'html' => '<section data-voodbuilder-section-block="vb-hero-1">Hero</section>',
                'css' => '.hero { color: red; }',
                'js' => '',
                'project' => null,
            ],
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '',
            'css' => '',
            'project' => ['pages' => []],
        ])->assertOk()->assertJson(['saved' => true]);

        $page->refresh();

        $this->assertSame('', $page->builder_payload['html']);
        $this->assertStringNotContainsString('vb-hero-1', (string) ($page->builder_payload['html'] ?? ''));
    }

    public function test_guest_cannot_save_editor_payload(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Grapes page',
            'slug' => 'grapes-page-guest',
            'builder' => PageBuilder::Visual,
            'published' => true,
        ]);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Nope</section>',
        ])->assertUnauthorized();
    }

    public function test_cannot_save_non_editor_page(): void
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
            'email' => 'admin-rich@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Rich page',
            'slug' => 'rich-page',
            'builder' => PageBuilder::RichEditor,
            'published' => true,
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Nope</section>',
        ])->assertForbidden();
    }

    public function test_rejects_oversized_html_payload(): void
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
            'email' => 'admin-big@example.com',
        ])->save();

        config(['voodbuilder.editor.payload.max_html_bytes' => 10]);

        $page = SitePage::query()->create([
            'title' => 'Grapes page big',
            'slug' => 'grapes-page-big',
            'builder' => PageBuilder::Visual,
            'published' => true,
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => str_repeat('a', 20),
        ])->assertUnprocessable();
    }
}
