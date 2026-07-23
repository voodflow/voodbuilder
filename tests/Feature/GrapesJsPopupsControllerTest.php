<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsPopupsControllerTest extends TestCase
{
    public function test_builder_user_can_create_popup_from_editor_api(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'popup-editor@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $this->actingAs($user)
            ->postJson(route('voodbuilder.grapesjs.popups.store'), [
                'name' => 'Newsletter signup',
                'enabled' => true,
                'paused' => false,
                'priority' => 5,
                'rules' => [
                    'trigger' => ['type' => 'load'],
                    'frequency' => ['mode' => 'always'],
                    'schedule' => ['start_at' => '', 'end_at' => '', 'weekly_day' => '', 'weekly_start_time' => '', 'weekly_end_time' => ''],
                    'targeting' => ['logged_in' => 'any', 'page_path' => ''],
                    'display' => ['width' => 'md', 'overlay' => true, 'close_on_overlay' => true, 'close_on_escape' => true],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('popup.name', 'Newsletter signup');

        $popup = BuilderPopup::query()->where('name', 'Newsletter signup')->first();

        $this->assertNotNull($popup);
        $this->assertTrue($popup->enabled);
        $this->assertFalse($popup->paused);
        $this->assertStringContainsString('Newsletter signup', (string) $popup->html);
    }

    public function test_popup_editor_controller_enables_grapesjs_editor_flag(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'popup-visual@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $popup = BuilderPopup::query()->create([
            'name' => 'Welcome visual',
            'enabled' => true,
            'priority' => 1,
            'html' => '<section class="p-4"><h2>Hi</h2></section>',
            'css' => '',
            'js' => '',
            'rules' => [],
        ]);

        $this->actingAs($user);
        request()->merge(['edit' => 1]);

        $view = app(\Voodflow\Voodbuilder\Http\Controllers\PopupEditorController::class)->show($popup);

        $this->assertSame('voodbuilder::pages.popup-editor', $view->name());
        $this->assertTrue($view->getData()['grapesJsEditor']);
        $this->assertIsArray($view->getData()['grapesJsConfig']);
        $this->assertTrue((bool) ($view->getData()['grapesJsConfig']['popupMode'] ?? false));
        $this->assertSame(
            \Voodflow\Voodbuilder\Support\SubThemeResolver::siteDefault(),
            $view->getData()['grapesJsConfig']['subTheme'] ?? null,
        );
        $this->assertSame('32rem', $view->getData()['grapesJsConfig']['popupDisplayWidth'] ?? null);

        $editorEntries = \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsAssets::pageViteEntries(true);
        $this->assertContains(
            \Voodflow\Voodbuilder\Support\VoodbuilderPaths::grapesJsViteEntry(),
            $editorEntries,
        );
        $this->assertFalse(
            collect($editorEntries)->contains(fn (string $entry): bool => str_ends_with($entry, 'site-runtime.js')),
        );
    }

    public function test_builder_user_can_load_popup_page_paths(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'popup-paths@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $this->actingAs($user)
            ->getJson(route('voodbuilder.grapesjs.popups.page-paths'))
            ->assertOk()
            ->assertJsonStructure(['paths' => [['value', 'label', 'group']]]);
    }

    public function test_updating_popup_replaces_weekly_days_instead_of_merging_indexes(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'popup-days@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $popup = BuilderPopup::query()->create([
            'name' => 'Weekly days popup',
            'enabled' => true,
            'paused' => false,
            'priority' => 1,
            'html' => '<section></section>',
            'css' => '',
            'js' => '',
            'rules' => [
                'schedule' => [
                    'weekly_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                    'weekly_day' => 'monday',
                    'timezone' => 'UTC',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->putJson(route('voodbuilder.grapesjs.popups.update', $popup), [
                'name' => 'Weekly days popup',
                'enabled' => true,
                'paused' => false,
                'priority' => 1,
                'rules' => [
                    'trigger' => ['type' => 'load'],
                    'frequency' => ['mode' => 'always'],
                    'schedule' => [
                        'start_at' => '',
                        'end_at' => '',
                        'weekly_days' => ['thursday'],
                        'weekly_day' => 'thursday',
                        'timezone' => 'UTC',
                    ],
                    'targeting' => ['logged_in' => 'any', 'page_path' => ''],
                    'display' => ['width' => 'md', 'overlay' => true, 'close_on_overlay' => true, 'close_on_escape' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('popup.rules.schedule.weekly_days', ['thursday']);

        $popup->refresh();

        $this->assertSame(['thursday'], $popup->rules['schedule']['weekly_days'] ?? null);
        $this->assertSame('thursday', $popup->rules['schedule']['weekly_day'] ?? null);
    }
}
