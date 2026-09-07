<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\BrandMarkAssets;
use Voodflow\Voodbuilder\Support\Editor\ChromeBrandLogos;
use Voodflow\Voodbuilder\Support\Editor\SiteNavConfig;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeBrandLogosTest extends TestCase
{
    public function test_falls_back_across_slots_when_one_is_missing(): void
    {
        $resolved = ChromeBrandLogos::resolve([
            'logo_desktop_light' => 'https://cdn.test/light.svg',
        ]);

        $this->assertSame('https://cdn.test/light.svg', $resolved['desktop_light']);
        $this->assertSame('https://cdn.test/light.svg', $resolved['desktop_dark']);
        $this->assertSame('https://cdn.test/light.svg', $resolved['mobile_light']);
        $this->assertSame('https://cdn.test/light.svg', $resolved['mobile_dark']);
        $this->assertTrue($resolved['has_any']);
    }

    public function test_prefers_dedicated_dark_and_mobile_slots(): void
    {
        $resolved = ChromeBrandLogos::resolve([
            'logo_desktop_light' => 'https://cdn.test/desk-light.svg',
            'logo_desktop_dark' => 'https://cdn.test/desk-dark.svg',
            'logo_mobile_light' => 'https://cdn.test/mob-light.svg',
            'logo_mobile_dark' => 'https://cdn.test/mob-dark.svg',
        ]);

        $this->assertSame('https://cdn.test/desk-light.svg', $resolved['desktop_light']);
        $this->assertSame('https://cdn.test/desk-dark.svg', $resolved['desktop_dark']);
        $this->assertSame('https://cdn.test/mob-light.svg', $resolved['mobile_light']);
        $this->assertSame('https://cdn.test/mob-dark.svg', $resolved['mobile_dark']);
    }

    public function test_empty_config_uses_animated_voodbuilder_mark(): void
    {
        $resolved = ChromeBrandLogos::resolve([]);

        $this->assertTrue($resolved['has_any']);
        $this->assertNotNull($resolved['desktop_light']);
        $this->assertStringContainsString('voodbuilder-mark.svg', (string) $resolved['desktop_light']);
        $this->assertFileExists(BrandMarkAssets::publicPath());
    }

    public function test_site_nav_config_normalizes_logo_and_visibility_flags(): void
    {
        $normalized = SiteNavConfig::normalize([
            'show_logo' => 0,
            'show_site_name' => 1,
            'logo_desktop_dark' => '  /storage/logos/dark.png  ',
            'logo_mobile_light' => '',
            'logo_size' => 'xl',
            'logo_size_mobile' => 'sm',
            'logo_full_width' => 1,
        ]);

        $this->assertFalse($normalized['show_logo']);
        $this->assertTrue($normalized['show_site_name']);
        $this->assertSame('/storage/logos/dark.png', $normalized['logo_desktop_dark']);
        $this->assertNull($normalized['logo_mobile_light']);
        $this->assertSame('xl', $normalized['logo_size']);
        $this->assertSame('sm', $normalized['logo_size_mobile']);
        $this->assertTrue($normalized['logo_full_width']);
    }

    public function test_logo_size_maps_to_tailwind_height_classes(): void
    {
        $this->assertSame('h-6', ChromeBrandLogos::heightClass('sm'));
        $this->assertSame('h-8', ChromeBrandLogos::heightClass('md'));
        $this->assertSame('h-10', ChromeBrandLogos::heightClass('lg'));
        $this->assertSame('h-12', ChromeBrandLogos::heightClass('xl'));
        $this->assertSame('lg', ChromeBrandLogos::normalizeSize('nope'));
        $this->assertStringContainsString('h-12', ChromeBrandLogos::desktopLogoClass('xl'));
        $this->assertStringContainsString('w-full', ChromeBrandLogos::desktopLogoClass('lg', true));
        $this->assertStringContainsString('h-6', ChromeBrandLogos::footerLogoClass('sm', true));
        $this->assertStringContainsString('rounded-full', ChromeBrandLogos::footerLogoClass('md', false));
        $this->assertStringContainsString('w-full', ChromeBrandLogos::footerLogoClass('md', false, true, true));
        $this->assertStringContainsString('object-contain', ChromeBrandLogos::footerLogoClass('md', false, true, false, true));
        $this->assertStringNotContainsString('rounded-full', ChromeBrandLogos::footerLogoClass('md', false, true, false, true));
    }
}
