<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageRevision;
use Voodflow\Voodbuilder\Modules\History\HistoryModule;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class HistoryModuleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.history.enabled', false);
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

    protected function tearDown(): void
    {
        EditorGate::authorizeUsing(null);

        parent::tearDown();
    }

    public function test_history_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(HistoryModule::ID));
        $this->assertFalse(Voodbuilder::modules()->isEnabled(HistoryModule::ID));
        $this->assertFalse(HistoryModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.editor.pages.revisions.index'));
        $this->assertFalse(Route::has('voodbuilder.editor.pages.revisions.restore'));
    }

    public function test_page_save_still_works_without_creating_revisions_when_history_disabled(): void
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
            'email' => 'history-off@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'No history',
            'slug' => 'no-history',
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
        ])->assertOk()->assertJson(['saved' => true]);

        $this->assertSame(0, SitePageRevision::query()->count());
        $page->refresh();
        $this->assertStringContainsString('Updated', (string) ($page->builder_payload['html'] ?? ''));
    }
}
