<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\BrandMarkAssets;
use Voodflow\Voodbuilder\Support\VoodbuilderSeo;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderSeoFaviconTest extends TestCase
{
    public function test_favicon_falls_back_to_voodflow_mark_when_unset(): void
    {
        VoodbuilderSettings::saveData([
            ...VoodbuilderSettings::defaults(),
            'favicon' => null,
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
}
