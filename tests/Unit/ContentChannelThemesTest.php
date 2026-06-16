<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ContentChannelRegistry;
use Voodflow\Vpress\Support\ContentChannelThemes;
use Voodflow\Vpress\Tests\TestCase;
use Illuminate\Support\Collection;

class ContentChannelThemesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(ContentChannelRegistry::class)->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'events';
            }

            public function label(): string
            {
                return 'Events';
            }

            public function routePatterns(): array
            {
                return ['vevents.*'];
            }

            public function subTheme(): ?string
            {
                return null;
            }

            public function search(string $term, int $limit = 20): Collection
            {
                return collect();
            }
        });
    }

    public function test_uses_package_default_when_no_override(): void
    {
        $channel = app(ContentChannelRegistry::class)->get('events');

        $this->assertNotNull($channel);
        $this->assertSame('events', ContentChannelThemes::resolveForChannel($channel));
    }

    public function test_admin_override_takes_precedence_over_package_default(): void
    {
        VpressSettings::query()->create([
            'data' => array_merge(VpressSettings::defaults(), [
                'content_channel_sub_themes' => [
                    'events' => 'news',
                ],
            ]),
        ]);
        VpressSettings::clearCache();

        $channel = app(ContentChannelRegistry::class)->get('events');

        $this->assertNotNull($channel);
        $this->assertSame('news', ContentChannelThemes::resolveForChannel($channel));
    }

    public function test_invalid_override_is_ignored(): void
    {
        VpressSettings::query()->create([
            'data' => array_merge(VpressSettings::defaults(), [
                'content_channel_sub_themes' => [
                    'events' => 'does-not-exist',
                ],
            ]),
        ]);
        VpressSettings::clearCache();

        $channel = app(ContentChannelRegistry::class)->get('events');

        $this->assertNotNull($channel);
        $this->assertSame('events', ContentChannelThemes::resolveForChannel($channel));
    }

    public function test_normalize_overrides_strips_empty_and_invalid_values(): void
    {
        $normalized = ContentChannelThemes::normalizeOverrides([
            'events' => 'news',
            'broken' => 'nope',
            'pages' => '',
        ]);

        $this->assertSame(['events' => 'news'], $normalized);
    }
}
