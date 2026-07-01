<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsElementConditionEvaluator;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsElementConditionEvaluatorTest extends TestCase
{
    public function test_any_group_match_shows_element(): void
    {
        $evaluator = new GrapesJsElementConditionEvaluator;

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

        $evaluator = new GrapesJsElementConditionEvaluator;

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
