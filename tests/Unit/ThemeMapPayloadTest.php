<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\ThemeMapPayload;
use Voodflow\Voodbuilder\Tests\TestCase;

class ThemeMapPayloadTest extends TestCase
{
    public function test_site_pages_edge_is_inherited_when_using_default_site_theme(): void
    {
        $this->assertTrue(ThemeMapPayload::edgeIsInherited('site_pages', 'site', SubThemeResolver::SITE));
    }

    public function test_site_pages_edge_is_explicit_when_using_custom_site_theme(): void
    {
        $this->assertFalse(ThemeMapPayload::edgeIsInherited('site_pages', 'site', 'landing-custom'));
    }

    public function test_channel_override_edge_is_explicit(): void
    {
        $this->assertFalse(ThemeMapPayload::edgeIsInherited('blog', 'override', 'news'));
    }

    public function test_channel_package_default_edge_is_inherited(): void
    {
        $this->assertTrue(ThemeMapPayload::edgeIsInherited('blog', 'package_default', 'blog'));
    }
}
