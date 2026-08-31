<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MarketingSiteContent;

/**
 * Local-only marketing site for the Voodflow plugin ecosystem.
 *
 * Run: php artisan db:seed --class="Voodflow\Voodbuilder\Database\Seeders\VoodflowMarketingSiteSeeder"
 *
 * View: /pages/a — e.g. http://localhost:8006/pages/a
 */
final class VoodflowMarketingSiteSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MarketingSiteContent::pages() as $slug => $definition) {
            SitePage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $definition['title'],
                    'builder' => PageBuilder::Visual,
                    'layout' => $definition['layout'] ?? 'landing',
                    'sub_theme' => $definition['sub_theme'] ?? 'events',
                    'published' => true,
                    'published_at' => now(),
                    'is_home' => ($definition['is_home'] ?? false),
                    'builder_payload' => [
                        'html' => $definition['html'],
                        'css' => $definition['css'] ?? '',
                        'project' => null,
                    ],
                    'content' => null,
                ],
            );
        }
    }
}
