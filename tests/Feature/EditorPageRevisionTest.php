<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageRevision;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorPageRevisionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_save_creates_revision_of_previous_payload(): void
    {
        $user = $this->makeUser();
        $page = SitePage::query()->create([
            'title' => 'Revision page',
            'slug' => 'revision-page',
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => '<section>Original</section>',
                'css' => '',
                'js' => '',
            ],
            'published' => true,
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Updated</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        $revision = SitePageRevision::query()->first();

        $this->assertNotNull($revision);
        $this->assertSame('<section>Original</section>', $revision->builder_payload['html']);
    }

    public function test_restore_revision_replaces_payload(): void
    {
        $user = $this->makeUser();
        $page = SitePage::query()->create([
            'title' => 'Restore page',
            'slug' => 'restore-page',
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => '<section>Current</section>',
                'css' => '',
                'js' => '',
            ],
            'published' => true,
        ]);

        $revision = SitePageRevision::query()->create([
            'site_page_id' => $page->getKey(),
            'builder_payload' => [
                'html' => '<section>Old</section>',
                'css' => '.old{}',
                'js' => '',
            ],
            'created_by' => $user->getKey(),
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.revisions.restore', [
            'sitePage' => $page,
            'revision' => $revision,
        ]))->assertOk();

        $page->refresh();

        $this->assertSame('<section>Old</section>', $page->builder_payload['html']);
        $this->assertSame('.old{}', $page->builder_payload['css']);
    }

    private function makeUser(): User
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
            'email' => 'admin-revisions@example.com',
        ])->save();

        return $user;
    }
}
