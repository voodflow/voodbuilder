<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Support\Editor\EditorRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * The boundary between "what may be built" and "what stays visible".
 *
 * Capabilities decide the first. They must never decide the second, because the second is
 * how a lapsed renewal — or an outage of our own licensing endpoint that outlives the
 * grace window — turns into missing sections on live customer sites. A licence may stop an
 * author from working; it may not unpublish what is already public.
 *
 * These tests run on Community, the edition an installation falls back to when the licence
 * cannot be resolved at all.
 */
final class PublishedContentIgnoresLicenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );
    }

    /**
     * A page holding a List repeat, as the editor stores it.
     *
     * The empty-state child is what the renderer shows when the collection yields nothing,
     * which is the case here — no model integration is registered. That is enough to tell
     * the two outcomes apart: the renderer strips `data-voodbuilder-repeat` when it runs,
     * and leaves the markup verbatim when it declines to.
     */
    private function pageWithRepeat(): SitePage
    {
        $page = new SitePage;
        $page->builder_payload = [
            'html' => <<<'HTML'
                <section>
                    <div data-voodbuilder-repeat="articles.list" data-voodbuilder-repeat-limit="3">
                        <article data-voodbuilder-repeat-item><h3>Title</h3></article>
                        <p data-voodbuilder-repeat-empty>Nothing published yet.</p>
                    </div>
                </section>
                HTML,
        ];

        return $page;
    }

    public function test_a_published_repeat_is_still_expanded_without_the_collections_entitlement(): void
    {
        if (! DynamicDataCollectionsBridge::renderingEnabled()) {
            $this->markTestSkipped('voodbuilder-dynamic-data companion package is not available.');
        }

        $this->assertFalse(Voodbuilder::can('dynamic-data.collections'));

        $html = app(EditorRenderer::class)->render($this->pageWithRepeat());

        // The renderer consumed the repeat instead of handing the raw markup to the visitor.
        $this->assertStringNotContainsString('data-voodbuilder-repeat', $html);
        $this->assertStringContainsString('Nothing published yet.', $html);
    }

    public function test_the_rendered_page_is_byte_identical_across_editions(): void
    {
        if (! DynamicDataCollectionsBridge::renderingEnabled()) {
            $this->markTestSkipped('voodbuilder-dynamic-data companion package is not available.');
        }

        $community = app(EditorRenderer::class)->render($this->pageWithRepeat());

        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_AGENCY),
        );

        $agency = app(EditorRenderer::class)->render($this->pageWithRepeat());

        $this->assertSame($agency, $community);
    }

    public function test_authoring_a_repeat_still_needs_the_entitlement(): void
    {
        if (! DynamicDataCollectionsBridge::renderingEnabled()) {
            $this->markTestSkipped('voodbuilder-dynamic-data companion package is not available.');
        }

        // Rendering is unconditional; offering the feature is not. Otherwise this change
        // would have given collections away rather than protecting published pages.
        $this->assertFalse(DynamicDataCollectionsBridge::authoringEnabled());
        $this->assertSame([], DynamicDataCollectionsBridge::repeatSourcesCatalog());
    }

    public function test_the_repeat_source_picker_is_empty_but_the_renderer_is_not_disabled(): void
    {
        if (! DynamicDataCollectionsBridge::renderingEnabled()) {
            $this->markTestSkipped('voodbuilder-dynamic-data companion package is not available.');
        }

        $this->assertTrue(DynamicDataCollectionsBridge::renderingEnabled());
        $this->assertFalse(DynamicDataCollectionsBridge::authoringEnabled());
    }
}
