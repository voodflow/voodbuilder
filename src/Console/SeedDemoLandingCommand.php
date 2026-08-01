<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\DemoEditorLanding;

/**
 * Artisan command: Seed Demo Landing.
 */
class SeedDemoLandingCommand extends Command
{
    protected $signature = 'voodbuilder:seed-demo-landing
                            {--slug=landing-page : Site page slug to update or create}
                            {--sub-theme= : Optional sub-theme slug}';

    protected $description = 'Seed a sample exhibitor-style landing as an Editor page';

    public function handle(): int
    {
        $slug = (string) $this->option('slug');
        $subTheme = (string) $this->option('sub-theme');

        $payload = DemoEditorLanding::payload();
        DemoEditorLanding::writeUtilitiesCatalog();

        $page = SitePage::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'title' => 'Demo Expo Landing',
                'builder' => PageBuilder::Visual,
                'layout' => 'landing',
                'sub_theme' => filled($subTheme) ? $subTheme : null,
                'published' => true,
                'published_at' => now(),
                'builder_payload' => $payload,
                'content' => null,
            ],
        );

        $this->components->info("Demo landing ready: {$page->getUrl()} (slug: {$slug})");

        return self::SUCCESS;
    }
}
