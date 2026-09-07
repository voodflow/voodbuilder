<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Contracts\DynamicPageProvider;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageBindingCatalog;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class DynamicPageBindingCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Voodbuilder::dynamicPageProvider(new class implements DynamicPageProvider
        {
            public function channelId(): string
            {
                return 'exhibitors';
            }

            public function channelLabel(): string
            {
                return 'Exhibitors';
            }

            public function claimableRoutes(): array
            {
                return [
                    'acme.show' => 'Show',
                    'acme.show-event' => 'Show event',
                ];
            }

            public function entityKeys(): array
            {
                return ['exhibitor', 'event'];
            }

            public function normalizeRouteName(string $routeName): string
            {
                return $routeName;
            }

            public function previewUrl(?SitePage $page = null): ?string
            {
                return null;
            }

            public function previewEntities(?SitePage $page = null): array
            {
                return [];
            }

            public function resolveRouteEntitiesFromSlugs(array $slugs): array
            {
                return [];
            }

            public function currentBindingSourceId(): string
            {
                return 'acme.current';
            }

            public function editorRouteEntityPatterns(): array
            {
                return [];
            }
        });
    }

    public function test_returns_empty_for_non_dynamic_page(): void
    {
        $page = new SitePage(['is_dynamic' => false]);

        $this->assertSame([], DynamicPageBindingCatalog::currentSourceIds($page));
    }

    public function test_maps_channel_provider_to_current_source(): void
    {
        $page = new SitePage([
            'is_dynamic' => true,
            'dynamic_channel' => 'exhibitors',
            'dynamic_routes' => ['acme.show'],
        ]);

        $this->assertSame(['acme.current'], DynamicPageBindingCatalog::currentSourceIds($page));
    }

    public function test_includes_related_source_when_registered(): void
    {
        Voodbuilder::dynamicPageRelatedBindingSource(
            'exhibitors',
            'events.current',
            static fn (SitePage $page): bool => in_array('acme.show-event', $page->dynamicRouteNames(), true),
        );

        $page = new SitePage([
            'is_dynamic' => true,
            'dynamic_channel' => 'exhibitors',
            'dynamic_routes' => ['acme.show-event'],
        ]);

        $this->assertSame(
            ['acme.current', 'events.current'],
            DynamicPageBindingCatalog::currentSourceIds($page),
        );
    }
}
