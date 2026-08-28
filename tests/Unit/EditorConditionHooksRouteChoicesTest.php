<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionHooks;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorConditionHooksRouteChoicesTest extends TestCase
{
    public function test_route_name_condition_exposes_select_choices_from_dynamic_page_registry(): void
    {
        $choices = $this->routeChoices();

        $this->assertNotEmpty($choices);
        $this->assertTrue(
            Collection::make($choices)->contains(
                fn (array $choice): bool => $choice['value'] === 'vevents.exhibitors.show',
            ),
        );

        $eventShow = Collection::make($choices)->firstWhere('value', 'vevents.exhibitors.show');
        $this->assertIsArray($eventShow);
        $this->assertSame('Exhibitor at event', $eventShow['label'] ?? null);
        $this->assertStringContainsString('vevents.exhibitors.show', (string) ($eventShow['title'] ?? ''));
    }

    public function test_user_logged_in_uses_boolean_value_meta(): void
    {
        $option = Collection::make(EditorConditionHooks::options())
            ->firstWhere('key', 'user_logged_in');

        $this->assertSame('boolean', $option['value']['type'] ?? null);
    }

    public function test_date_conditions_use_date_value_meta(): void
    {
        foreach (['date_before', 'date_after'] as $key) {
            $option = Collection::make(EditorConditionHooks::options())
                ->firstWhere('key', $key);

            $this->assertSame('date', $option['value']['type'] ?? null, $key);
        }
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function routeChoices(): array
    {
        /** @var array{value: array{choices?: list<array{value: string, label: string}>}} $routeOption */
        $routeOption = Collection::make(EditorConditionHooks::options())
            ->firstWhere('key', 'route_name');

        return $routeOption['value']['choices'] ?? [];
    }
}
