<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Support\ActiveThemeMap;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;

class ActiveThemeMapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(ContentChannelRegistry::class)->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'docs';
            }

            public function label(): string
            {
                return 'Documentation';
            }

            public function routePatterns(): array
            {
                return ['vdocs.*'];
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

        app(ContentChannelRegistry::class)->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'tutorials';
            }

            public function label(): string
            {
                return 'Tutorials';
            }

            public function routePatterns(): array
            {
                return ['vtuts.*'];
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

    public function test_themes_in_use_lists_unique_themes_from_site_and_channels(): void
    {
        config()->set('voodbuilder.content_channel_docss.docs', 'docs');
        config()->set('voodbuilder.content_channel_docss.tutorials', 'docs');

        $options = ActiveThemeMap::themesInUseOptions([
            'sub_theme' => 'site',
            'content_channel_sub_themes' => [
                'docs' => 'docs',
            ],
        ]);

        $this->assertSame(['site', 'docs'], array_keys($options));
    }

    public function test_assignments_mark_channel_override_source(): void
    {
        config()->set('voodbuilder.content_channel_docss.tutorials', 'docs');

        $assignments = ActiveThemeMap::assignments([
            'sub_theme' => 'site',
            'content_channel_sub_themes' => [
                'tutorials' => 'docs',
            ],
        ]);

        $tutorial = collect($assignments)->firstWhere('area_id', 'tutorials');

        $this->assertNotNull($tutorial);
        $this->assertSame('docs', $tutorial['theme_id']);
        $this->assertSame('override', $tutorial['source']);
    }
}
