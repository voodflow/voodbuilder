<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsChromeLayoutSaveTest extends TestCase
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

        Filament::registerPanel(
            Panel::make()
                ->id('admin')
                ->path('admin'),
        );

        PageBuilderAccess::authorizeUsing(
            static fn (): bool => auth()->check(),
        );
    }

    public function test_admin_can_save_chrome_layout_content(): void
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
            'email' => 'chrome-admin@example.com',
        ])->save();

        $layout = ChromeLayout::query()->create([
            'name' => 'Main chrome',
            'slug' => 'main-chrome',
            'html' => '<header>Old</header>',
            'enabled' => true,
        ]);

        $this->actingAs($user);

        $response = $this->putJson(route('voodbuilder.grapesjs.chrome-layouts.content.update', $layout), [
            'html' => '<header data-voodbuilder-chrome="nav">Nav</header><main data-voodbuilder-content-slot="main"></main>',
            'css' => '.nav { color: blue; }',
            'js' => '',
        ]);

        $response->assertOk()->assertJson(['saved' => true]);

        $layout->refresh();

        $this->assertStringContainsString('data-voodbuilder-chrome="nav"', (string) $layout->html);
        $this->assertStringContainsString('data-voodbuilder-content-slot="main"', (string) $layout->html);
    }

    public function test_guest_cannot_save_chrome_layout_content(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Locked chrome',
            'slug' => 'locked-chrome',
            'html' => '<header>Old</header>',
            'enabled' => true,
        ]);

        $this->putJson(route('voodbuilder.grapesjs.chrome-layouts.content.update', $layout), [
            'html' => '<header>Nope</header>',
        ])->assertUnauthorized();
    }
}
