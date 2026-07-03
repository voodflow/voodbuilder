<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingImageAltResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class BindingImageAltResolverTest extends TestCase
{
    public function test_detects_placeholder_alt_text(): void
    {
        $this->assertTrue(BindingImageAltResolver::isPlaceholderAlt('[Series · List item: Featured image]'));
        $this->assertTrue(BindingImageAltResolver::isPlaceholderAlt('Dynamic image'));
        $this->assertFalse(BindingImageAltResolver::isPlaceholderAlt('Advanced Filament'));
    }
}
