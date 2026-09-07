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
use Voodflow\Voodbuilder\Enums\SitePageRevisionKind;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageRevision;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorPageRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        EditorGate::authorizeUsing(null);
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

        Filament::registerPanel(
            Panel::make()
                ->id('admin')
                ->path('admin'),
        );

        EditorGate::authorizeUsing(
            fn (SitePage $page): bool => $page->usesEditorBuilder() && auth()->check(),
        );

        // The revisions routes sit behind the page builder permission, like the rest of
        // the editor surface.
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);
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

    public function test_autosave_parks_work_without_publishing_it(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-page', '<section>Published</section>');

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section>Work in progress</section>',
            'css' => '',
            'js' => '',
        ])->assertOk()->assertJson(['autosaved' => true]);

        $page->refresh();

        // The whole point: nothing the author has not saved may reach the live page.
        $this->assertSame('<section>Published</section>', $page->builder_payload['html']);

        $autosave = SitePageRevision::query()->autosaves()->sole();

        $this->assertStringContainsString('Work in progress', $autosave->builder_payload['html']);
        $this->assertSame(SitePageRevisionKind::Autosave, $autosave->kind);
    }

    public function test_autosave_does_not_pile_up_identical_drafts(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-idle', '<section>Published</section>');

        $this->actingAs($user);

        $payload = ['html' => '<section>Draft</section>', 'css' => '', 'js' => ''];

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), $payload)->assertOk();
        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), $payload)->assertOk();
        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), $payload)->assertOk();

        // An idle editor ticking the timer must not evict older drafts that still hold
        // recoverable work.
        $this->assertSame(1, SitePageRevision::query()->autosaves()->count());
    }

    public function test_autosave_is_skipped_when_the_draft_matches_the_saved_page(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-clean', '<section>Published</section>');

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section>Published</section>',
            'css' => '',
            'js' => '',
        ])->assertOk()->assertJson(['autosaved' => false]);

        $this->assertSame(0, SitePageRevision::query()->autosaves()->count());
    }

    public function test_autosaves_have_their_own_budget(): void
    {
        config(['voodbuilder.editor.revisions.max_autosaves_to_keep' => 2]);

        $user = $this->makeUser();
        $page = $this->makePage('autosave-budget', '<section>Published</section>');

        $this->actingAs($user);

        foreach (['One', 'Two', 'Three', 'Four'] as $body) {
            $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
                'html' => "<section>{$body}</section>",
                'css' => '',
                'js' => '',
            ])->assertOk();
        }

        $autosaves = SitePageRevision::query()->autosaves()->orderByDesc('id')->get();

        $this->assertCount(2, $autosaves);
        $this->assertStringContainsString('Four', $autosaves->first()->builder_payload['html']);
    }

    public function test_autosaves_do_not_evict_the_authors_own_history(): void
    {
        config([
            'voodbuilder.editor.revisions.max_to_keep' => 3,
            'voodbuilder.editor.revisions.max_autosaves_to_keep' => 2,
        ]);

        $user = $this->makeUser();
        $page = $this->makePage('autosave-mixed', '<section>V1</section>');

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>V2</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        foreach (['A', 'B', 'C', 'D'] as $body) {
            $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
                'html' => "<section>{$body}</section>",
                'css' => '',
                'js' => '',
            ])->assertOk();
        }

        // The manual revision from the save must survive a burst of autosaves.
        $this->assertSame(1, SitePageRevision::query()->manual()->count());
        $this->assertSame(2, SitePageRevision::query()->autosaves()->count());
    }

    public function test_saving_clears_the_recovery_offer(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-cleared', '<section>Published</section>');

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section>Draft</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        $this->assertSame(1, SitePageRevision::query()->autosaves()->count());

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Draft</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        // Otherwise the author is greeted by a recovery prompt for the page they just saved.
        $this->assertSame(0, SitePageRevision::query()->autosaves()->count());
    }

    public function test_revisions_list_separates_history_from_the_recovery_offer(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-listing', '<section>V1</section>');

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>V2</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section>Unsaved</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        $response = $this->getJson(route('voodbuilder.editor.pages.revisions.index', $page))
            ->assertOk();

        $response->assertJsonCount(1, 'revisions');
        $this->assertNotNull($response->json('autosave.id'));
    }

    public function test_restoring_an_autosave_publishes_it_and_drops_the_offer(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-restore', '<section>Published</section>');

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section>Recovered</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        $autosave = SitePageRevision::query()->autosaves()->sole();

        $this->postJson(route('voodbuilder.editor.pages.revisions.restore', [
            'sitePage' => $page,
            'revision' => $autosave,
        ]))->assertOk();

        $page->refresh();

        $this->assertStringContainsString('Recovered', $page->builder_payload['html']);
        $this->assertSame(0, SitePageRevision::query()->autosaves()->count());
    }

    public function test_restoring_an_autosave_publishes_it_with_a_stylesheet(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-styles', '<section>Published</section>');

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section class="bg-vp-bg p-8">Recovered</section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        $autosave = SitePageRevision::query()->autosaves()->sole();

        // The draft skips the Tailwind pass to keep the timer cheap, so restoring it must
        // run that pass — otherwise the page goes live unstyled.
        $this->assertSame('', $autosave->builder_payload['css']);

        $this->postJson(route('voodbuilder.editor.pages.revisions.restore', [
            'sitePage' => $page,
            'revision' => $autosave,
        ]))->assertOk();

        $page->refresh();

        $this->assertStringContainsString('--color-vp-bg', $page->builder_payload['css']);
    }

    public function test_autosave_refuses_to_park_markup_a_save_would_have_stripped(): void
    {
        $user = $this->makeUser();
        $page = $this->makePage('autosave-xss', '<section>Published</section>');

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section onclick="steal()">Draft<script>steal()</script></section>',
            'css' => '',
            'js' => '',
        ])->assertOk();

        $stored = SitePageRevision::query()->autosaves()->sole()->builder_payload['html'];

        // A draft is restorable, so it is a publishing path with a delay: it cannot be a
        // hole in the save-time sanitization.
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onclick', $stored);
    }

    public function test_autosave_is_refused_without_page_builder_access(): void
    {
        PageBuilderAccess::authorizeUsing(static fn (): bool => false);

        $user = $this->makeUser();
        $page = $this->makePage('autosave-forbidden', '<section>Published</section>');

        $this->actingAs($user);

        $this->postJson(route('voodbuilder.editor.pages.autosave', $page), [
            'html' => '<section>Draft</section>',
            'css' => '',
            'js' => '',
        ])->assertForbidden();
    }

    private function makePage(string $slug, string $html): SitePage
    {
        return SitePage::query()->create([
            'title' => $slug,
            'slug' => $slug,
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => $html,
                'css' => '',
                'js' => '',
            ],
            'published' => true,
        ]);
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
