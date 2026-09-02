<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Support\DynamicPages\AbstractDynamicPageProvider;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionHooks;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorConditionHooksRouteChoicesTest extends TestCase
{
    public function test_route_name_condition_falls_back_to_free_text_with_no_provider_registered(): void
    {
        /** @var array{value: array{type: string}} $routeOption */
        $routeOption = Collection::make(EditorConditionHooks::options())
            ->firstWhere('key', 'route_name');

        // A bare install has no companion claiming routes, so the author must still be able
        // to type a route name rather than face an empty select.
        $this->assertSame([], $this->routeChoices());
        $this->assertSame('text', $routeOption['value']['type']);
    }

    public function test_route_name_condition_exposes_select_choices_from_dynamic_page_registry(): void
    {
        // Register a provider rather than asserting against a companion package: the core
        // suite must not depend on vevents being installed to cover its own mechanism.
        app(DynamicPageRegistry::class)->register(new FakeExhibitorPageProvider);

        $choices = $this->routeChoices();

        $this->assertNotEmpty($choices);
        $this->assertTrue(
            Collection::make($choices)->contains(
                fn (array $choice): bool => $choice['value'] === 'demo.exhibitors.show',
            ),
        );

        $eventShow = Collection::make($choices)->firstWhere('value', 'demo.exhibitors.show');
        $this->assertIsArray($eventShow);
        $this->assertSame('Exhibitor at event', $eventShow['label'] ?? null);
        $this->assertStringContainsString('demo.exhibitors.show', (string) ($eventShow['title'] ?? ''));
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

/**
 * Stands in for a companion that claims public routes (vevents, vexhibitors, …).
 */
final class FakeExhibitorPageProvider extends AbstractDynamicPageProvider
{
    public function channelId(): string
    {
        return 'demo-exhibitors';
    }

    public function channelLabel(): string
    {
        return 'Demo exhibitors';
    }

    public function claimableRoutes(): array
    {
        return [
            'demo.exhibitors.show' => 'Exhibitor at event / /exhibitors/{slug}/events/{eventSlug}',
        ];
    }

    public function entityKeys(): array
    {
        return ['exhibitor'];
    }

    public function currentBindingSourceId(): string
    {
        return 'demo-exhibitors.current';
    }
}
