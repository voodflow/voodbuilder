<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\Editor\EditorLinkTargets;
use Voodflow\Voodbuilder\Tests\TestCase;

final class EditorLinkTargetsTest extends TestCase
{
    #[Test]
    public function it_includes_app_routes_in_the_catalog(): void
    {
        $catalog = EditorLinkTargets::catalog();

        $this->assertArrayHasKey('pages', $catalog);
        $this->assertArrayHasKey('menuItems', $catalog);
        $this->assertArrayHasKey('routes', $catalog);
        $this->assertIsArray($catalog['routes']);

        foreach ($catalog['routes'] as $route) {
            $this->assertArrayHasKey('id', $route);
            $this->assertArrayHasKey('label', $route);
            $this->assertArrayHasKey('url', $route);
            $this->assertArrayHasKey('requiredParams', $route);
            $this->assertIsArray($route['requiredParams']);
        }
    }

    #[Test]
    public function it_resolves_named_routes_or_returns_hash_when_missing(): void
    {
        $missing = EditorLinkTargets::resolveRoute('voodbuilder.route.that.does.not.exist');
        $this->assertSame('#', $missing);

        $catalog = EditorLinkTargets::catalog();
        $paramFree = collect($catalog['routes'])
            ->first(fn (array $route): bool => ($route['requiredParams'] ?? []) === [] && ($route['url'] ?? '#') !== '#');

        if ($paramFree === null) {
            $this->assertTrue(true);

            return;
        }

        $url = EditorLinkTargets::resolveRoute((string) $paramFree['id']);
        $this->assertSame($paramFree['url'], $url);
    }
}
