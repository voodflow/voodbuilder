<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class PopupsPublicControllerTest extends TestCase
{
    public function test_returns_enabled_popups_for_current_page(): void
    {
        BuilderPopup::query()->create([
            'name' => 'Visible',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<body><div>Promo</div></body>',
        ]);

        BuilderPopup::query()->create([
            'name' => 'Disabled',
            'enabled' => false,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Hidden</div>',
        ]);

        $response = $this->getJson(route('voodbuilder.popups.public'));

        $response->assertOk();
        $response->assertJsonCount(1, 'popups');
        $response->assertJsonPath('popups.0.name', 'Visible');
        $response->assertJsonPath('popups.0.html', '<div>Promo</div>');
    }

    public function test_filters_paused_and_out_of_schedule_popups(): void
    {
        BuilderPopup::query()->create([
            'name' => 'Paused',
            'enabled' => true,
            'paused' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Paused</div>',
        ]);

        BuilderPopup::query()->create([
            'name' => 'Expired',
            'enabled' => true,
            'paused' => false,
            'rules' => [
                'schedule' => [
                    'end_at' => now()->subDay()->toIso8601String(),
                ],
            ],
            'html' => '<div>Expired</div>',
        ]);

        BuilderPopup::query()->create([
            'name' => 'Scheduled',
            'enabled' => true,
            'paused' => false,
            'rules' => [
                'schedule' => [
                    'start_at' => now()->subHour()->toIso8601String(),
                    'end_at' => now()->addHour()->toIso8601String(),
                ],
            ],
            'html' => '<div>Scheduled</div>',
        ]);

        $this->getJson(route('voodbuilder.popups.public'))
            ->assertOk()
            ->assertJsonCount(1, 'popups')
            ->assertJsonPath('popups.0.name', 'Scheduled');
    }

    public function test_filters_popups_by_current_locale(): void
    {
        BuilderPopup::query()->create([
            'name' => 'Italian',
            'locale' => 'it',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>IT</div>',
        ]);

        BuilderPopup::query()->create([
            'name' => 'English',
            'locale' => 'en',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>EN</div>',
        ]);

        BuilderPopup::query()->create([
            'name' => 'Any',
            'locale' => null,
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Any</div>',
        ]);

        $this->withoutMiddleware([
            \Voodflow\Voodbuilder\Http\Middleware\ApplyVoodbuilderSiteConfig::class,
        ]);
        $this->app->setLocale('it');

        $response = $this->getJson(route('voodbuilder.popups.public'));

        $response->assertOk();
        $response->assertJsonCount(2, 'popups');
        $names = collect($response->json('popups'))->pluck('name')->all();
        $this->assertContains('Italian', $names);
        $this->assertContains('Any', $names);
        $this->assertNotContains('English', $names);
    }

    public function test_authenticated_builder_user_can_save_popup_content(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'editor@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $popup = BuilderPopup::query()->create([
            'name' => 'Offer',
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Old</div>',
        ]);

        $this->actingAs($user)
            ->putJson(route('voodbuilder.grapesjs.popups.content.update', $popup), [
                'html' => '<section class="voodbuilder-gjs-section"><div class="p-6">New popup</div></section>',
                'css' => '.p-6{padding:1.5rem;}',
            ])
            ->assertOk()
            ->assertJsonPath('saved', true);

        $popup->refresh();

        $this->assertStringContainsString('New popup', (string) $popup->html);
        $this->assertStringContainsString('.p-6', (string) $popup->css);
    }
}
