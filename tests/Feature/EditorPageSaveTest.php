<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Storage;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\Editor\PageCssArtifactStore;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorPageSaveTest extends TestCase
{
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

        // Editor routes also use EnsurePageBuilderAccess (not only EditorGate).
        PageBuilderAccess::authorizeUsing(static fn (): bool => auth()->check());

        EditorGate::authorizeUsing(
            fn (SitePage $page): bool => $page->usesEditorBuilder() && auth()->check(),
        );
    }

    public function test_admin_can_save_editor_payload(): void
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
            'email' => 'admin@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes page',
            'slug' => 'grapes-page',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->assertTrue($page->usesEditorBuilder());

        $this->actingAs($user);

        $response = $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Updated</section>',
            'css' => '.updated { color: red; }',
            'project' => ['pages' => []],
        ]);

        $response->assertOk()->assertJson(['saved' => true]);

        $page->refresh();
        $storedCss = PageCssArtifactStore::resolveCss($page->builder_payload ?? []);

        $this->assertSame('<section class="voodbuilder-editor-section bg-vp-bg">Updated</section>', $page->builder_payload['html']);
        $this->assertStringContainsString('.updated', $storedCss);
        $this->assertStringContainsString('color: red', $storedCss);
        $this->assertStringContainsString('.bg-vp-bg', $storedCss);
        $this->assertStringContainsString('var(--color-vp-bg)', $storedCss);
        $this->assertSame('', $page->builder_payload['js']);
        $this->assertStringContainsString('.bg-vp-bg', (string) $response->json('css'));
        $this->assertArrayHasKey(\Voodflow\Voodbuilder\Http\Controllers\EditorPageController::SAVE_INPUT_HASH_KEY, $page->builder_payload);
        $this->assertArrayHasKey(\Voodflow\Voodbuilder\Http\Controllers\EditorPageController::CSS_CLASS_FINGERPRINT_KEY, $page->builder_payload);

        $again = $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Updated</section>',
            'css' => '.updated { color: red; }',
            'project' => ['pages' => []],
        ]);

        $again->assertOk()->assertJson([
            'saved' => true,
            'css_unchanged' => true,
        ]);
        $this->assertNull($again->json('css'));
    }

    public function test_admin_can_save_editor_component_scripts(): void
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
            'email' => 'admin-js@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes tabs',
            'slug' => 'grapes-tabs',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->actingAs($user);

        $js = 'var items = document.querySelectorAll("#tabs");';

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<div id="tabs"></div>',
            'css' => '',
            'js' => $js,
        ])->assertOk();

        $page->refresh();

        $this->assertSame($js, $page->builder_payload['js']);
        $this->assertSame($js, $page->renderedScripts());
    }

    public function test_save_migrates_legacy_brand_classes(): void
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
            'email' => 'admin-migrate@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes migrate',
            'slug' => 'grapes-migrate',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<a class="bg-indigo-500 text-white">Button</a>',
            'css' => '.btn { background-color: #6366f1; }',
            'project' => ['pages' => []],
        ])->assertOk();

        $page->refresh();

        $this->assertStringContainsString('bg-indigo-500', $page->builder_payload['html']);
        $this->assertStringContainsString('var(--color-vp-brand-', PageCssArtifactStore::resolveCss($page->builder_payload ?? []));
    }

    public function test_admin_can_clear_page_html_on_save(): void
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
            'email' => 'admin-clear@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes clear',
            'slug' => 'grapes-clear',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
            'builder_payload' => [
                'html' => '<section data-voodbuilder-section-block="vb-hero-1">Hero</section>',
                'css' => '.hero { color: red; }',
                'js' => '',
                'project' => null,
            ],
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '',
            'css' => '',
            'project' => ['pages' => []],
        ])->assertOk()->assertJson(['saved' => true]);

        $page->refresh();

        $this->assertSame('', $page->builder_payload['html']);
        $this->assertStringNotContainsString('vb-hero-1', (string) ($page->builder_payload['html'] ?? ''));
    }

    public function test_guest_cannot_save_editor_payload(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Grapes page',
            'slug' => 'grapes-page-guest',
            'builder' => PageBuilder::Visual,
            'published' => true,
        ]);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Nope</section>',
        ])->assertUnauthorized();
    }

    public function test_cannot_save_non_editor_page(): void
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
            'email' => 'admin-rich@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Rich page',
            'slug' => 'rich-page',
            'builder' => PageBuilder::RichEditor,
            'published' => true,
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section>Nope</section>',
        ])->assertForbidden();
    }

    public function test_admin_can_save_form_like_html_with_json_data_attributes(): void
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
            'email' => 'admin-form-save@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Grapes form page',
            'slug' => 'grapes-form-page',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
            'builder_payload' => [
                'html' => '<section class="voodbuilder-editor-section bg-vp-bg">persist-ok-marker</section>',
                'css' => '',
                'js' => '',
                'project' => null,
            ],
        ]);

        $this->actingAs($user);

        $formHtml = <<<'HTML'
<section class="voodbuilder-editor-section vforms-managed-form-section w-full py-10" data-vforms-managed-form="form-1" data-vforms-block="managed">
  <div class="vforms-managed-form" data-vforms-form-mount="form-1">
    <form class="vforms-form" method="post" action="/forms/form-1/submit">
      <div class="vforms-form-field" data-vforms-field="name" data-vforms-visibility="{&quot;logic&quot;:&quot;and&quot;,&quot;rules&quot;:[]}">
        <label for="vforms-form-name">Name</label>
        <input id="vforms-form-name" type="text" name="name" />
      </div>
    </form>
  </div>
</section>
HTML;

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => $formHtml,
            'css' => '',
            'project' => ['pages' => []],
        ])->assertOk()->assertJson(['saved' => true]);

        $page->refresh();
        $savedHtml = (string) ($page->builder_payload['html'] ?? '');

        $this->assertStringContainsString('data-vforms-managed-form="form-1"', $savedHtml);
        $this->assertStringContainsString('data-vforms-field="name"', $savedHtml);
        $this->assertStringContainsString('Name', $savedHtml);
        $this->assertStringNotContainsString('persist-ok-marker', $savedHtml);
        $this->assertStringNotContainsString('data-vforms-visibility="{"', $savedHtml);
        $this->assertStringNotContainsString('&amp;quot;', $savedHtml);

        $initial = EditorGate::initialPayload($page);
        $this->assertStringContainsString('data-vforms-managed-form="form-1"', $initial['html']);
        $this->assertStringContainsString('Name', $initial['html']);
    }

    public function test_rejects_oversized_html_payload(): void
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
            'email' => 'admin-big@example.com',
        ])->save();

        config(['voodbuilder.editor.payload.max_html_bytes' => 10]);

        $page = SitePage::query()->create([
            'title' => 'Grapes page big',
            'slug' => 'grapes-page-big',
            'builder' => PageBuilder::Visual,
            'published' => true,
        ]);

        $this->actingAs($user);

        $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => str_repeat('a', 20),
        ])->assertUnprocessable();
    }

    public function test_large_compiled_css_is_stored_as_artifact_not_in_json_payload(): void
    {
        Storage::fake('public');

        config([
            'voodbuilder.editor.payload.css_artifact_threshold_bytes' => 10,
            'voodbuilder.editor.payload.css_artifact_disk' => 'public',
            'voodbuilder.editor.payload.max_css_bytes' => 1_000_000,
        ]);

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
            'email' => 'admin-artifact@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Artifact save',
            'slug' => 'artifact-save',
            'builder' => PageBuilder::Visual,
            'layout' => 'landing',
            'published' => true,
        ]);

        $this->actingAs($user);

        // Author-sized request stays well under max_css; server compile merges utilities/theme.
        $response = $this->putJson(route('voodbuilder.editor.pages.update', $page), [
            'html' => '<section class="flex items-center justify-between gap-4 p-8 bg-vp-bg text-vp-text-1"><h1 id="hero">Hi</h1></section>',
            'css' => '#hero { color: red; }',
        ]);

        $response->assertOk()->assertJson(['saved' => true]);

        $page->refresh();
        $payload = $page->builder_payload ?? [];
        $fullFromResponse = (string) $response->json('css');

        $this->assertGreaterThan(10, strlen($fullFromResponse));
        $this->assertArrayHasKey(PageCssArtifactStore::META_KEY, $payload);
        $this->assertIsArray($payload[PageCssArtifactStore::META_KEY]);
        $this->assertTrue(strlen((string) ($payload['css'] ?? '')) < strlen($fullFromResponse));
        $this->assertNotNull($page->pageCssStylesheetUrl());
        $this->assertSame($fullFromResponse, PageCssArtifactStore::resolveCss($payload));
        $this->assertStringContainsString('#hero', (string) ($payload['css'] ?? ''));
    }
}
