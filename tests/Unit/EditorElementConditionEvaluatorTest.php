<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Http\Request;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorElementConditionEvaluator;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorElementConditionEvaluatorTest extends TestCase
{
    public function test_page_path_contains_slash_matches_homepage_only(): void
    {
        $evaluator = new EditorElementConditionEvaluator;
        $definition = [
            'match' => 'all',
            'sets' => [
                ['conditions' => [['key' => 'page_path', 'compare' => 'contains', 'value' => '/']]],
            ],
        ];

        $this->app->instance('request', Request::create('/blog', 'GET'));
        $this->assertFalse($evaluator->passes($definition));

        $this->app->instance('request', Request::create('/', 'GET'));
        $this->assertTrue($evaluator->passes($definition));
    }

    public function test_any_group_match_shows_element(): void
    {
        $evaluator = new EditorElementConditionEvaluator;

        $this->assertTrue($evaluator->passes([
            'match' => 'any',
            'sets' => [
                ['conditions' => [['key' => 'locale', 'compare' => '==', 'value' => 'it']]],
                ['conditions' => [['key' => 'locale', 'compare' => '==', 'value' => 'en']]],
            ],
        ]));
    }

    public function test_all_groups_must_match_when_configured(): void
    {
        app()->setLocale('it');

        $evaluator = new EditorElementConditionEvaluator;

        $definition = [
            'match' => 'all',
            'sets' => [
                ['conditions' => [['key' => 'locale', 'compare' => '==', 'value' => 'it']]],
                ['conditions' => [['key' => 'locale', 'compare' => '==', 'value' => 'en']]],
            ],
        ];

        $this->assertFalse($evaluator->passes($definition));

        $definition['sets'][1]['conditions'][0]['value'] = 'it';

        $this->assertTrue($evaluator->passes($definition));
    }
}
