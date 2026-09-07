<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * Security checks for Editor block catalog endpoints.
 */
final class EditorBlocksControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_catalog_requires_page_builder_access(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Guest builder',
            'email' => 'guest-builder@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => false);
        PageBuilderAccess::authorizeUsing(static fn (): bool => false);

        $this->actingAs($user)
            ->getJson(route('voodbuilder.editor.blocks'))
            ->assertForbidden();
    }

    public function test_blocks_catalog_does_not_expose_internal_paths(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'blocks-editor@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $response = $this->actingAs($user)
            ->getJson(route('voodbuilder.editor.blocks'));

        $response->assertOk();

        $payload = json_encode($response->json(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('.env', $payload);
        $this->assertStringNotContainsString('APP_KEY', $payload);
        $this->assertStringNotContainsString('storage/', $payload);
    }

    public function test_blocks_catalog_returns_blocks_array_only(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'blocks-list@example.com',
        ])->save();

        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $this->actingAs($user)
            ->getJson(route('voodbuilder.editor.blocks'))
            ->assertOk()
            ->assertJsonStructure(['blocks']);
    }

    public function test_blocks_catalog_requires_authentication(): void
    {
        $this->getJson(route('voodbuilder.editor.blocks'))
            ->assertUnauthorized();
    }
}
