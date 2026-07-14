<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class SubThemeResolverPagesChannelTest extends TestCase
{
    public function test_site_page_without_sub_theme_uses_site_default_theme(): void
    {
        $page = new SitePage([
            'sub_theme' => null,
        ]);

        $this->assertSame(SubThemeResolver::siteDefault(), SubThemeResolver::forPage($page));
    }

    public function test_site_page_explicit_sub_theme_takes_precedence(): void
    {
        $page = new SitePage([
            'sub_theme' => 'docs',
        ]);

        $this->assertSame('docs', SubThemeResolver::forPage($page));
    }
}
