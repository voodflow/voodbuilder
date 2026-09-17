<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\TagCollection;
use RalphJSmit\Laravel\SEO\Tags\FaviconTag;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\BrandMarkAssets;
use Voodflow\Voodbuilder\Support\Seo\MediaFaviconLinkTag;
use Voodflow\Voodbuilder\Support\VoodbuilderSeo;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderSeoFaviconTest extends TestCase
{
    public function test_favicon_falls_back_to_voodflow_mark_when_unset(): void
    {
        VoodbuilderSettings::saveData([
            ...VoodbuilderSettings::defaults(),
            'favicon' => null,
            'favicon_dark' => null,
        ]);

        $seoData = VoodbuilderSeo::applyDefaults(new SEOData);

        $this->assertNotNull($seoData->favicon);
        $this->assertStringContainsString('voodbuilder-mark.svg', (string) $seoData->favicon);
        $this->assertSame(VoodbuilderSettings::faviconUrl(), $seoData->favicon);
    }

    public function test_brand_mark_replaces_empty_laravel_favicon_ico(): void
    {
        $faviconPath = public_path('favicon.ico');

        file_put_contents($faviconPath, '');

        BrandMarkAssets::ensurePublished();

        $this->assertFileExists($faviconPath);
        $this->assertGreaterThan(0, filesize($faviconPath));
        $this->assertFileExists(BrandMarkAssets::publicPngPath());
    }

    public function test_apply_defaults_fills_blank_title_from_site_title(): void
    {
        VoodbuilderSettings::saveData([
            ...VoodbuilderSettings::defaults(),
            'site_title' => 'Soundmit Fair',
        ]);

        $seoData = VoodbuilderSeo::applyDefaults(new SEOData);

        $this->assertSame('Soundmit Fair', $seoData->title);
    }

    public function test_should_replace_inferred_homepage_host_title(): void
    {
        $this->assertTrue(VoodbuilderSeo::shouldReplaceTitle(null));
        $this->assertTrue(VoodbuilderSeo::shouldReplaceTitle(''));
        $this->assertFalse(VoodbuilderSeo::shouldReplaceTitle('About us'));

        url()->forceRootUrl('http://localhost:8014');
        $this->get('http://localhost:8014/');

        $this->assertTrue(VoodbuilderSeo::shouldReplaceTitle('Localhost:8014'));
    }

    public function test_transform_tags_emits_light_and_dark_favicon_links(): void
    {
        VoodbuilderSettings::saveData([
            ...VoodbuilderSettings::defaults(),
            'favicon' => 'voodbuilder/favicons/light.png',
            'favicon_dark' => 'voodbuilder/favicons/dark.png',
        ]);

        $tags = TagCollection::make([
            FaviconTag::initialize(new SEOData(favicon: VoodbuilderSettings::faviconUrl())),
        ]);

        $transformed = VoodbuilderSeo::transformTags($tags);

        $mediaTags = $transformed->filter(fn (mixed $tag): bool => $tag instanceof MediaFaviconLinkTag)->values();

        $this->assertCount(3, $mediaTags);
        $this->assertArrayNotHasKey('media', $mediaTags[0]->attributes);
        $this->assertSame('(prefers-color-scheme: dark)', $mediaTags[1]->attributes['media'] ?? null);
        $this->assertSame('(prefers-color-scheme: light)', $mediaTags[2]->attributes['media'] ?? null);
        $this->assertTrue($transformed->filter(fn (mixed $tag): bool => $tag instanceof FaviconTag)->isEmpty());
    }
}
