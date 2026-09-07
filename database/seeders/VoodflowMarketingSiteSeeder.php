<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MarketingSiteContent;
use Voodflow\Voodbuilder\Support\MarketingSiteLayout;
use Voodflow\Voodbuilder\Support\MarketingSiteMenus;

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
        $layout = MarketingSiteLayout::seed();

        $this->seedPages($layout);
        $this->removeStalePages();
        MarketingSiteMenus::seed();
    }

    protected function seedPages(ChromeLayout $layout): void
    {
        foreach (MarketingSiteContent::pages() as $slug => $definition) {
            SitePage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $definition['title'],
                    'locale' => MarketingSiteMenus::LOCALE,
                    'builder' => PageBuilder::Visual,
                    'layout' => $definition['layout'] ?? 'landing',
                    'chrome_layout_id' => $layout->getKey(),
                    'sub_theme' => $definition['sub_theme'] ?? 'site',
                    'published' => true,
                    'published_at' => now(),
                    'is_home' => (bool) ($definition['is_home'] ?? false),
                    'hide_site_nav' => false,
                    'hide_site_footer' => false,
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

    protected function removeStalePages(): void
    {
        $validSlugs = array_keys(MarketingSiteContent::pages());

        SitePage::query()
            ->where('slug', 'like', 'a%')
            ->whereNotIn('slug', $validSlugs)
            ->delete();
    }
}
