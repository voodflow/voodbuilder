<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

final class EditorAuthorJsCapabilityTest extends TestCase
{
    private function pageWithJs(string $js): SitePage
    {
        $page = new SitePage;
        $page->builder_payload = ['html' => '<div>x</div>', 'js' => $js];

        return $page;
    }

    public function test_author_js_is_withheld_without_the_capability(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_PROFESSIONAL),
        );

        $this->assertFalse(Voodbuilder::can('pages.custom-js'));
        $this->assertNull(app(EditorRenderer::class)->js($this->pageWithJs('console.log(1)')));
    }

    public function test_author_js_is_served_with_the_capability(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_AGENCY),
        );

        $this->assertTrue(Voodbuilder::can('pages.custom-js'));
        $this->assertSame(
            'console.log(1)',
            app(EditorRenderer::class)->js($this->pageWithJs('console.log(1)')),
        );
    }

    public function test_empty_js_stays_null_regardless_of_edition(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_AGENCY),
        );

        $this->assertNull(app(EditorRenderer::class)->js($this->pageWithJs('')));
    }
}
