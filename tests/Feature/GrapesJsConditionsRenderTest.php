<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsElementConditionRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsConditionsRenderTest extends TestCase
{
    public function test_fixture_conditions_keep_matching_locale_blocks(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__).'/Fixtures/0.0.11/sample-page.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $page = new SitePage([
            'title' => 'Conditions',
            'slug' => 'conditions',
            'locale' => 'en',
            'builder' => PageBuilder::GrapesJs,
        ]);

        app()->setLocale('en');

        $html = app(GrapesJsElementConditionRenderer::class)->render(
            (string) $fixture['condition_html'],
            $page,
        );

        $this->assertStringContainsString('Visible in EN', $html);
        $this->assertStringNotContainsString('Visible in IT', $html);
        $this->assertStringNotContainsString('data-voodbuilder-conditions', $html);
    }

    public function test_fixture_conditions_hide_non_matching_locale_blocks(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__).'/Fixtures/0.0.11/sample-page.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $page = new SitePage([
            'title' => 'Conditions IT',
            'slug' => 'conditions-it',
            'locale' => 'it',
            'builder' => PageBuilder::GrapesJs,
        ]);

        app()->setLocale('it');

        $html = app(GrapesJsElementConditionRenderer::class)->render(
            (string) $fixture['condition_html'],
            $page,
        );

        $this->assertStringNotContainsString('Visible in EN', $html);
        $this->assertStringContainsString('Visible in IT', $html);
    }
}
