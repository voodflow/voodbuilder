<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\PopupRulesEvaluator;
use Voodflow\Voodbuilder\Tests\TestCase;

class PopupRulesEvaluatorTest extends TestCase
{
    public function test_passes_when_no_targeting_sets(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Welcome',
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Hello</div>',
        ]);

        $this->assertTrue(app(PopupRulesEvaluator::class)->passesTargeting($popup));
    }

    public function test_filters_by_page_path(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Blog only',
            'rules' => [
                'targeting' => [
                    'match' => 'all',
                    'sets' => [
                        [
                            'conditions' => [
                                ['key' => 'page_path', 'compare' => 'contains', 'value' => '/blog'],
                            ],
                        ],
                    ],
                ],
            ],
            'html' => '<div>Blog promo</div>',
        ]);

        $evaluator = app(PopupRulesEvaluator::class);

        $this->get('/blog/post-1');
        $this->assertTrue($evaluator->passesTargeting($popup->fresh()));

        $this->get('/about');
        $this->assertFalse($evaluator->passesTargeting($popup->fresh()));
    }
}
