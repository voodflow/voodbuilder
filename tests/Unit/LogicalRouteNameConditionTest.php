<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorElementConditionEvaluator;
use Voodflow\Voodbuilder\Support\Editor\Conditions\LogicalRouteName;
use Voodflow\Voodbuilder\Tests\TestCase;

class LogicalRouteNameConditionTest extends TestCase
{
    public function test_logical_route_name_strips_locale_prefix(): void
    {
        $this->assertSame(
            'vevents.exhibitors.show',
            LogicalRouteName::normalize('vevents.en.exhibitors.show'),
        );
    }

    public function test_logical_route_name_leaves_a_name_without_a_locale_untouched(): void
    {
        // The old implementation dropped the middle segment of any `vevents.*` name, so an
        // author's condition on the logical name could never match the live request.
        $this->assertSame(
            'vevents.exhibitors.show',
            LogicalRouteName::normalize('vevents.exhibitors.show'),
        );
        $this->assertSame(
            'vdocs.articles.index',
            LogicalRouteName::normalize('vdocs.articles.index'),
        );
    }

    public function test_logical_route_name_strips_locales_for_any_package_prefix(): void
    {
        // Nothing about this belongs to one companion: the same shape ships in every
        // package that publishes localized routes.
        $this->assertSame(
            'vdocs.articles.show',
            LogicalRouteName::normalize('vdocs.it.articles.show'),
        );
        $this->assertSame(
            'vtuts.show',
            LogicalRouteName::normalize('vtuts.pt-br.show'),
        );
    }

    public function test_route_name_condition_matches_locale_prefixed_request_route(): void
    {
        Route::get('/_test/event-exhibitor', fn () => 'ok')->name('vevents.en.exhibitors.show');

        $this->app['router']->getRoutes()->refreshNameLookups();

        $response = $this->get('/_test/event-exhibitor');

        $response->assertOk();

        $evaluator = new EditorElementConditionEvaluator;

        $this->assertTrue($evaluator->passes([
            'match' => 'any',
            'sets' => [[
                'conditions' => [[
                    'key' => 'route_name',
                    'compare' => '==',
                    'value' => 'vevents.exhibitors.show',
                ]],
            ]],
        ]));

        $this->assertFalse($evaluator->passes([
            'match' => 'any',
            'sets' => [[
                'conditions' => [[
                    'key' => 'route_name',
                    'compare' => '==',
                    'value' => 'vexhibitors.show',
                ]],
            ]],
        ]));
    }
}
