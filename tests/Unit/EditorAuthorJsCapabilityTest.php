<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutRenderer;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
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

    public function test_a_save_does_not_store_a_script_it_would_refuse_to_run(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_PROFESSIONAL),
        );

        // The check used to live only in the renderer, so the script was accepted, written
        // to the database and silently dropped on the way out. Now it never lands.
        $normalized = EditorGate::normalizePayload([
            'html' => '<div>x</div>',
            'js' => 'console.log(1)',
        ]);

        $this->assertSame('', $normalized['js']);
    }

    public function test_a_save_keeps_the_script_with_the_capability(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_AGENCY),
        );

        $normalized = EditorGate::normalizePayload([
            'html' => '<div>x</div>',
            'js' => 'console.log(1)',
        ]);

        $this->assertSame('console.log(1)', $normalized['js']);
    }

    public function test_an_autosaved_draft_cannot_park_a_script_either(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_PROFESSIONAL),
        );

        // A draft is restorable, so parking a script in one would be a way around the save.
        $draft = EditorGate::sanitizeDraftPayload([
            'html' => '<div>x</div>',
            'js' => 'console.log(1)',
        ]);

        $this->assertSame('', $draft['js']);
    }

    public function test_chrome_layout_scripts_answer_to_the_same_policy(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_PROFESSIONAL),
        );

        $layout = new ChromeLayout;
        $layout->html = '<header>h</header><main data-voodbuilder-content-slot></main>';
        $layout->js = 'console.log("header")';

        // Header/footer JS reaches every public page through chrome-app.blade.php. It used
        // to bypass the check entirely, which made the layout the way to run a script
        // without the capability the page editor demands.
        $this->assertSame('', app(ChromeLayoutRenderer::class)->render($layout)['js']);
    }

    public function test_chrome_layout_scripts_are_served_with_the_capability(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_AGENCY),
        );

        $layout = new ChromeLayout;
        $layout->html = '<header>h</header><main data-voodbuilder-content-slot></main>';
        $layout->js = 'console.log("header")';

        $this->assertSame('console.log("header")', app(ChromeLayoutRenderer::class)->render($layout)['js']);
    }
}
