<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Http\Request;
use Voodflow\Voodbuilder\Support\Editor\EditorHostChrome;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * The editor context signal companions rely on to stand down.
 *
 * Companion packages (cookie banners, chat widgets) read the request attribute without
 * importing anything from this package, so its behaviour is part of the public contract:
 * it must be set for authors taking over the viewport and stay unset for visitors.
 */
class EditorHostChromeContextTest extends TestCase
{
    protected function tearDown(): void
    {
        PageBuilderAccess::authorizeUsing(null);

        parent::tearDown();
    }

    public function test_context_is_not_active_on_a_plain_page_request(): void
    {
        $this->bindRequest('http://localhost/about');

        $this->assertFalse(EditorHostChrome::isActive());
        $this->assertFalse(EditorHostChrome::shouldSuppressHostRender());
        $this->assertFalse(EditorHostChrome::isActive());
    }

    public function test_context_stays_inactive_for_a_visitor_who_appends_the_edit_flag(): void
    {
        // A cookie banner that disappears because a visitor typed ?edit=1 is a consent
        // failure, so the flag alone must never be enough to switch the context on.
        PageBuilderAccess::authorizeUsing(static fn (): bool => false);

        $this->bindRequest('http://localhost/about?edit=1');

        $this->assertFalse(EditorHostChrome::shouldSuppressHostRender());
        $this->assertFalse(EditorHostChrome::isActive());
    }

    public function test_context_activates_for_an_author_who_can_use_the_builder(): void
    {
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $this->bindRequest('http://localhost/about?edit=1');

        $this->assertTrue(EditorHostChrome::shouldSuppressHostRender());
        $this->assertTrue(EditorHostChrome::isActive());
    }

    public function test_context_activates_when_the_caller_already_resolved_editor_mode(): void
    {
        $this->bindRequest('http://localhost/about');

        $this->assertTrue(EditorHostChrome::shouldSuppressHostRender(editorEditor: true));
        $this->assertTrue(EditorHostChrome::isActive());
    }

    public function test_mark_active_uses_the_documented_attribute_name(): void
    {
        $request = $this->bindRequest('http://localhost/about');

        EditorHostChrome::markActive();

        // Companions match this literal string; renaming it silently breaks every one.
        $this->assertSame('voodbuilder.editor_active', EditorHostChrome::REQUEST_ATTRIBUTE);
        $this->assertTrue($request->attributes->getBoolean('voodbuilder.editor_active'));
    }

    private function bindRequest(string $url): Request
    {
        $request = Request::create($url);

        $this->app->instance('request', $request);

        return $request;
    }
}
