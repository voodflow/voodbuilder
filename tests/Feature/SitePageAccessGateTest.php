<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Enums\PageVisibility;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Http\Controllers\SitePageUnlockController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageCredential;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SitePageAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageAccessGateTest extends TestCase
{
    protected function defineWebRoutes($router): void
    {
        $router->get('pages/{slug}', [SitePageController::class, 'show'])->name('voodbuilder.pages.show');
        $router->post('pages/{slug}/unlock', SitePageUnlockController::class)->name('voodbuilder.pages.unlock');
        $router->get('login', fn () => 'login')->name('login');
        $router->get('register', fn () => 'register')->name('register');
    }

    protected function setUp(): void
    {
        parent::setUp();

        PageBuilderAccess::authorizeUsing(fn (): bool => false);
    }

    public function test_registered_page_shows_gate_for_guests(): void
    {
        $page = $this->makePublishedPage([
            'slug' => 'members-only',
            'visibility' => PageVisibility::Registered,
        ]);

        $this->get('/pages/members-only')
            ->assertOk()
            ->assertSee(__('voodbuilder::gate.registered_title'), false)
            ->assertDontSee('Secret body', false);
    }

    public function test_password_protected_page_shows_form_instead_of_content(): void
    {
        $page = $this->makePublishedPage([
            'slug' => 'secret',
            'password_protected' => true,
        ]);

        SitePageCredential::query()->create([
            'site_page_id' => $page->id,
            'email' => null,
            'password' => Hash::make('opensesame'),
        ]);

        $this->get('/pages/secret')
            ->assertOk()
            ->assertSee(__('voodbuilder::gate.password.eyebrow'), false)
            ->assertSee(__('voodbuilder::gate.password.submit'), false)
            ->assertDontSee('Secret body', false);
    }

    public function test_correct_password_unlocks_page_for_session(): void
    {
        $page = $this->makePublishedPage([
            'slug' => 'vault',
            'password_protected' => true,
        ]);

        SitePageCredential::query()->create([
            'site_page_id' => $page->id,
            'email' => null,
            'password' => Hash::make('opensesame'),
        ]);

        $this->from('/pages/vault')
            ->post('/pages/vault/unlock', ['password' => 'opensesame'])
            ->assertRedirect('/pages/vault');

        $this->get('/pages/vault')
            ->assertOk()
            ->assertSee('Secret body', false)
            ->assertDontSee(__('voodbuilder::gate.password.submit'), false);
    }

    public function test_email_password_pair_requires_matching_email(): void
    {
        $page = $this->makePublishedPage([
            'slug' => 'paired',
            'password_protected' => true,
        ]);

        SitePageCredential::query()->create([
            'site_page_id' => $page->id,
            'email' => 'alice@example.com',
            'password' => Hash::make('secret'),
        ]);

        $this->from('/pages/paired')
            ->post('/pages/paired/unlock', [
                'email' => 'bob@example.com',
                'password' => 'secret',
            ])
            ->assertSessionHasErrors('password');

        $this->from('/pages/paired')
            ->post('/pages/paired/unlock', [
                'email' => 'alice@example.com',
                'password' => 'secret',
            ])
            ->assertRedirect('/pages/paired');

        $this->assertTrue(SitePageAccess::isUnlocked($page->fresh()));
    }

    public function test_subscriber_gate_blocks_authenticated_non_subscribers(): void
    {
        $user = $this->createUser();

        $this->makePublishedPage([
            'slug' => 'premium',
            'visibility' => PageVisibility::Subscriber,
        ]);

        $this->actingAs($user)
            ->get('/pages/premium')
            ->assertOk()
            ->assertSee(__('voodbuilder::gate.subscriber_title_authenticated'), false)
            ->assertDontSee('Secret body', false);
    }

    public function test_password_bypasses_subscriber_visibility(): void
    {
        $user = $this->createUser();

        $page = $this->makePublishedPage([
            'slug' => 'preview-premium',
            'visibility' => PageVisibility::Subscriber,
            'password_protected' => true,
        ]);

        SitePageCredential::query()->create([
            'site_page_id' => $page->id,
            'email' => null,
            'password' => Hash::make('preview'),
        ]);

        $this->actingAs($user)
            ->get('/pages/preview-premium')
            ->assertOk()
            ->assertSee(__('voodbuilder::gate.subscriber_title_authenticated'), false)
            ->assertSee(__('voodbuilder::gate.password.or_access_code'), false)
            ->assertDontSee('Secret body', false);

        $this->actingAs($user)
            ->from('/pages/preview-premium')
            ->post('/pages/preview-premium/unlock', ['password' => 'preview'])
            ->assertRedirect('/pages/preview-premium');

        $this->actingAs($user)
            ->get('/pages/preview-premium')
            ->assertOk()
            ->assertSee('Secret body', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makePublishedPage(array $attributes = []): SitePage
    {
        return SitePage::query()->create(array_merge([
            'title' => 'Secret Page',
            'slug' => 'secret-page',
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => '<section class="voodbuilder-editor-section">Secret body</section>',
                'css' => '',
                'js' => '',
            ],
            'layout' => 'page',
            'is_home' => false,
            'published' => true,
            'published_at' => now(),
            'visibility' => PageVisibility::Public,
            'password_protected' => false,
        ], $attributes));
    }

    protected function createUser(): User
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Member',
            'email' => 'member@example.com',
        ]);
        $user->save();

        return $user;
    }
}
