<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Voodflow\Vevents\Support\SoundmitLandingAssets;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\SoundmitGrapesJsLanding;

class SeedSoundmitGrapesLandingCommand extends Command
{
    protected $signature = 'voodbuilder:seed-soundmit-grapes-landing
                            {--slug=landing-page : Site page slug to update or create}
                            {--sub-theme=events : Sub-theme slug (events, blog, news, or empty)}
                            {--install-assets : Download hero/split images when vevents is installed}';

    protected $description = 'Seed the Soundmit exhibitor landing as a GrapesJS page';

    public function handle(): int
    {
        $slug = (string) $this->option('slug');
        $subTheme = (string) $this->option('sub-theme');
        $assets = [];

        if ($this->option('install-assets') && class_exists(SoundmitLandingAssets::class)) {
            $assets = SoundmitLandingAssets::install();
            $this->components->info('Soundmit landing assets installed.');
        }

        $payload = SoundmitGrapesJsLanding::payload($assets);
        SoundmitGrapesJsLanding::writeUtilitiesCatalog();

        $page = SitePage::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'title' => 'Soundmit Exhibitor Landing',
                'builder' => PageBuilder::GrapesJs,
                'layout' => 'landing',
                'sub_theme' => filled($subTheme) ? $subTheme : null,
                'published' => true,
                'published_at' => now(),
                'builder_payload' => $payload,
                'content' => null,
            ],
        );

        $this->components->info("GrapesJS Soundmit landing ready: {$page->getUrl()} (slug: {$slug})");

        return self::SUCCESS;
    }
}
