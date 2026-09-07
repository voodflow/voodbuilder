<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Voodflow\Voodbuilder\Contracts\DynamicPageProvider;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRequestContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;

class DynamicPageRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        DynamicPageRequestContext::flush();

        parent::tearDown();
    }

    #[Test]
    public function registry_exposes_channel_and_route_options(): void
    {
        $registry = new DynamicPageRegistry;
        $registry->register(new class implements DynamicPageProvider
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
                return ['vexhibitors.show' => 'Show /{slug}'];
            }

            public function entityKeys(): array
            {
                return ['exhibitor'];
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
                return [
                    ['regex' => '^/acme/([^/]+)$', 'keys' => ['exhibitor']],
                ];
            }
        });

        $this->assertSame(['exhibitors' => 'Exhibitors'], $registry->channelOptions());
        $this->assertSame(['vexhibitors.show' => 'Show /{slug}'], $registry->routeOptions('exhibitors'));
        $this->assertSame([
            ['regex' => '^/acme/([^/]+)$', 'keys' => ['exhibitor']],
        ], $registry->editorRouteEntityPatterns());
    }

    #[Test]
    public function binding_context_reads_route_entities_from_request_context(): void
    {
        $page = new SitePage(['title' => 'Template', 'locale' => 'en', 'is_dynamic' => false]);
        $entity = new class extends Model
        {
            public string $name = 'Acme';
        };

        DynamicPageRequestContext::bind($page, 'exhibitors', ['exhibitor' => $entity]);

        $context = BindingContext::forPage($page);

        $this->assertSame($entity, $context->routeItem);
        $this->assertSame($entity, $context->routeEntity('exhibitor'));
        $this->assertSame('exhibitors', $context->dynamicChannel);
    }
}
