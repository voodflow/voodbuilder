<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\FilamentServiceProvider;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * Shield-less Filament install: open Layout visual editor outside /admin.
 */
class ChromeLayoutEditorAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        PageBuilderAccess::authorizeUsing(null);

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

        config()->set('voodbuilder.chrome_layouts.enabled', true);
        config()->set('voodbuilder.authorization.driver', 'auto');

        app(PanelRegistry::class)->register(
            Panel::make()
                ->id('admin')
                ->path('admin')
                ->default(),
        );

        // No custom authorizer — exercise AdminAccess like a real Community install.
        PageBuilderAccess::authorizeUsing(null);
    }

    public function test_authenticated_admin_can_open_chrome_layout_visual_editor(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'layout-editor@example.com',
        ])->save();

        $layout = ChromeLayout::query()->create([
            'name' => 'Site shell',
            'slug' => 'site-shell',
            'enabled' => true,
            'is_default' => true,
            'html' => '<div data-voodbuilder-content-slot></div>',
        ]);

        $this->actingAs($user)
            ->get(route('voodbuilder.chrome-layouts.editor', [
                'chromeLayout' => $layout,
                'edit' => 1,
            ]))
            ->assertOk();
    }

    public function test_guest_cannot_open_chrome_layout_visual_editor(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Site shell',
            'slug' => 'site-shell-guest',
            'enabled' => true,
            'html' => '<div data-voodbuilder-content-slot></div>',
        ]);

        $this->get(route('voodbuilder.chrome-layouts.editor', [
            'chromeLayout' => $layout,
            'edit' => 1,
        ]))->assertRedirect();
    }
}
