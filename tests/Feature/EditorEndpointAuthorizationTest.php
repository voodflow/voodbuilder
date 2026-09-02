<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * Every endpoint under `voodbuilder/editor` must require the page builder permission.
 *
 * `auth` alone is not a boundary: these endpoints spawn Node processes, write to the public
 * disk and enumerate the block catalog, so any registered site user could reach them.
 */
final class EditorEndpointAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsNonBuilder(): self
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Registered visitor',
            'email' => 'visitor@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => false);
        PageBuilderAccess::authorizeUsing(static fn (): bool => false);

        $this->actingAs($user);

        return $this;
    }

    public function test_compile_css_requires_page_builder_access(): void
    {
        $this->actingAsNonBuilder()
            ->postJson(route('voodbuilder.editor.compile-css'), [
                'html' => '<div class="p-4">hi</div>',
            ])
            ->assertForbidden();
    }

    public function test_code_highlight_requires_page_builder_access(): void
    {
        $this->actingAsNonBuilder()
            ->postJson(route('voodbuilder.editor.code.highlight'), [
                'language' => 'php',
                'code' => 'echo 1;',
            ])
            ->assertForbidden();
    }

    public function test_media_upload_requires_page_builder_access(): void
    {
        $this->actingAsNonBuilder()
            ->postJson(route('voodbuilder.editor.upload'))
            ->assertForbidden();
    }

    public function test_media_index_requires_page_builder_access(): void
    {
        $this->actingAsNonBuilder()
            ->getJson(route('voodbuilder.editor.media.index'))
            ->assertForbidden();
    }

    public function test_link_targets_requires_page_builder_access(): void
    {
        $this->actingAsNonBuilder()
            ->getJson(route('voodbuilder.editor.link-targets'))
            ->assertForbidden();
    }

    public function test_compile_css_rejects_guests(): void
    {
        $this->postJson(route('voodbuilder.editor.compile-css'), [
            'html' => '<div></div>',
        ])->assertUnauthorized();
    }
}
