<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Carbon;
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

    public function test_schedule_date_window_is_respected(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Scheduled',
            'rules' => [
                'schedule' => [
                    'start_at' => '2026-07-23T09:00',
                    'end_at' => '2026-07-23T18:00',
                ],
            ],
            'html' => '<div>Scheduled</div>',
        ]);

        $evaluator = app(PopupRulesEvaluator::class);

        $this->assertFalse($evaluator->isActiveNow($popup, Carbon::parse('2026-07-23 08:59:00')));
        $this->assertTrue($evaluator->isActiveNow($popup, Carbon::parse('2026-07-23 12:00:00')));
        $this->assertFalse($evaluator->isActiveNow($popup, Carbon::parse('2026-07-23 18:01:00')));
    }

    public function test_schedule_weekly_window_is_respected(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Weekly',
            'rules' => [
                'schedule' => [
                    'weekly_days' => ['monday', 'thursday'],
                    'timezone' => 'UTC',
                    'weekly_start_time' => '09:00',
                    'weekly_end_time' => '12:00',
                ],
            ],
            'html' => '<div>Weekly</div>',
        ]);

        $evaluator = app(PopupRulesEvaluator::class);

        $this->assertTrue($evaluator->isActiveNow($popup, Carbon::parse('2026-07-20 10:00:00', 'UTC')));
        $this->assertFalse($evaluator->isActiveNow($popup, Carbon::parse('2026-07-20 08:59:00', 'UTC')));
        $this->assertFalse($evaluator->isActiveNow($popup, Carbon::parse('2026-07-21 10:00:00', 'UTC')));
        $this->assertTrue($evaluator->isActiveNow($popup, Carbon::parse('2026-07-23 10:00:00', 'UTC')));
    }

    public function test_legacy_weekly_day_string_is_still_supported(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Legacy weekly',
            'rules' => [
                'schedule' => [
                    'weekly_day' => 'monday',
                    'timezone' => 'UTC',
                ],
            ],
            'html' => '<div>Weekly</div>',
        ]);

        $evaluator = app(PopupRulesEvaluator::class);

        $this->assertTrue($evaluator->isActiveNow($popup, Carbon::parse('2026-07-20 10:00:00', 'UTC')));
        $this->assertFalse($evaluator->isActiveNow($popup, Carbon::parse('2026-07-21 10:00:00', 'UTC')));
    }

    public function test_schedule_respects_timezone(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'TZ',
            'rules' => [
                'schedule' => [
                    'start_at' => '2026-07-23T09:00',
                    'timezone' => 'Europe/Rome',
                ],
            ],
            'html' => '<div>TZ</div>',
        ]);

        $evaluator = app(PopupRulesEvaluator::class);

        $this->assertFalse($evaluator->isActiveNow($popup, Carbon::parse('2026-07-23 06:30:00', 'UTC')));
        $this->assertTrue($evaluator->isActiveNow($popup, Carbon::parse('2026-07-23 07:30:00', 'UTC')));
    }

    public function test_matches_locale_filters_language_specific_popups(): void
    {
        $all = BuilderPopup::query()->create([
            'name' => 'All locales',
            'locale' => null,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>All</div>',
        ]);

        $italian = BuilderPopup::query()->create([
            'name' => 'Italian only',
            'locale' => 'it',
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>IT</div>',
        ]);

        $evaluator = app(PopupRulesEvaluator::class);

        app()->setLocale('it');
        $this->assertTrue($evaluator->matchesLocale($all));
        $this->assertTrue($evaluator->matchesLocale($italian));

        app()->setLocale('en');
        $this->assertTrue($evaluator->matchesLocale($all));
        $this->assertFalse($evaluator->matchesLocale($italian));
    }
}
