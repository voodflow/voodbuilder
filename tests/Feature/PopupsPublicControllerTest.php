<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use App\Models\User;
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

    public function test_authenticated_builder_user_can_save_popup_content(): void
    {
        $user = User::query()->create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
        ]);

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
