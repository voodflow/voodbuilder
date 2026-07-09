<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsPopupsControllerTest extends TestCase
{
    public function test_builder_user_can_create_popup_from_editor_api(): void
    {
        $user = User::query()->create([
            'name' => 'Editor',
            'email' => 'popup-editor@example.com',
        ]);

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $this->actingAs($user)
            ->postJson(route('voodbuilder.grapesjs.popups.store'), [
                'name' => 'Newsletter signup',
                'enabled' => true,
                'priority' => 5,
                'rules' => [
                    'trigger' => ['type' => 'load'],
                    'frequency' => ['mode' => 'always'],
                    'targeting' => ['logged_in' => 'any', 'page_path' => ''],
                    'display' => ['width' => 'md', 'overlay' => true, 'close_on_overlay' => true, 'close_on_escape' => true],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('popup.name', 'Newsletter signup');

        $popup = BuilderPopup::query()->where('name', 'Newsletter signup')->first();

        $this->assertNotNull($popup);
        $this->assertTrue($popup->enabled);
        $this->assertStringContainsString('Newsletter signup', (string) $popup->html);
    }

    public function test_builder_user_can_load_popup_page_paths(): void
    {
        $user = User::query()->create([
            'name' => 'Editor',
            'email' => 'popup-paths@example.com',
        ]);

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $this->actingAs($user)
            ->getJson(route('voodbuilder.grapesjs.popups.page-paths'))
            ->assertOk()
            ->assertJsonStructure(['paths' => [['value', 'label', 'group']]]);
    }
}
