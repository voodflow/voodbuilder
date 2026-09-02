<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use ReflectionException;
use ReflectionMethod;
use Voodflow\Voodbuilder\Http\Middleware\EnsurePageBuilderAccess;
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

    /**
     * Media endpoints answer to media permissions, not to the page builder permission.
     *
     * They used to be this package's, gated by EnsurePageBuilderAccess. They are now
     * voodflow/vmedia's, and it gates them on `Create:MediaItem` — which is the right
     * question to ask: the same user can already add media from the media library screen,
     * so demanding page builder access at the upload endpoint would deny nothing while
     * implying a boundary that is not there.
     *
     * The point still worth enforcing is that they are gated by *something*, which
     * test_every_editor_route_is_gated_or_explicitly_public checks across the whole prefix.
     */
    public function test_media_endpoints_are_not_reachable_without_authorization(): void
    {
        Gate::define('usePageBuilder', static fn (): bool => false);

        $this->postJson(route('voodbuilder.editor.upload'))->assertUnauthorized();
        $this->getJson(route('voodbuilder.editor.media.index'))->assertUnauthorized();

        $this->actingAsNonBuilder()
            ->postJson(route('voodbuilder.editor.upload'))
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

    /**
     * Sweep the whole prefix rather than named endpoints.
     *
     * Companions register their own groups under `voodbuilder/editor`, and a companion can
     * override a URI the core already gated: voodbuilder-dynamic-data re-registered
     * `bindings` without a gate and silently won. Enumerating the router is the only way
     * that regression shows up in CI.
     */
    public function test_every_editor_route_is_gated_or_explicitly_public(): void
    {
        // Public form submission is the one intentional exception: visitors post to it.
        $publicByDesign = ['voodbuilder.editor.forms.submit'];

        $ungated = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'voodbuilder/editor')) {
                continue;
            }

            if (in_array($route->getName(), $publicByDesign, true)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            if (in_array(EnsurePageBuilderAccess::class, $middleware, true)) {
                continue;
            }

            // Otherwise the controller (or a companion middleware) must gate it itself.
            if ($this->hasOwnAuthorizationGate($route->getActionName())) {
                continue;
            }

            $ungated[] = $route->uri().' ['.($route->getName() ?? 'unnamed').']';
        }

        $this->assertSame(
            [],
            $ungated,
            "Editor routes reachable by any authenticated user:\n".implode("\n", $ungated),
        );
    }

    /**
     * Whether the controller behind a route performs its own authorization check.
     */
    private function hasOwnAuthorizationGate(string $action): bool
    {
        if (! str_contains($action, '@') && ! str_ends_with($action, 'Controller')) {
            $class = $action;
            $method = '__invoke';
        } else {
            [$class, $method] = array_pad(explode('@', $action, 2), 2, '__invoke');
        }

        if (! class_exists($class)) {
            return false;
        }

        try {
            $file = (new ReflectionMethod($class, $method))->getFileName();
        } catch (ReflectionException) {
            return false;
        }

        if ($file === false) {
            return false;
        }

        $source = (string) file_get_contents($file);

        foreach (['PageBuilderAccess', 'EditorGate::canEdit', 'EditorChromeLayoutEditorGate', 'EntitlementGate', 'authorize'] as $marker) {
            if (str_contains($source, $marker)) {
                return true;
            }
        }

        return false;
    }
}
