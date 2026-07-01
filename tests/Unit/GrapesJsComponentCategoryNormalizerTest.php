<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentCategoryNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsComponentCategoryNormalizerTest extends TestCase
{
    public function test_normalizes_case_insensitive_matches(): void
    {
        $this->assertSame('Hero', GrapesJsComponentCategoryNormalizer::normalize('hero'));
        $this->assertSame('Hero', GrapesJsComponentCategoryNormalizer::normalize('HERO'));
        $this->assertSame('Hero', GrapesJsComponentCategoryNormalizer::normalize(' Hero '));
    }

    public function test_maps_known_aliases(): void
    {
        $this->assertSame('Content', GrapesJsComponentCategoryNormalizer::normalize('Sections'));
        $this->assertSame('Content', GrapesJsComponentCategoryNormalizer::normalize('section'));
        $this->assertSame('Features', GrapesJsComponentCategoryNormalizer::normalize('feature'));
    }

    public function test_falls_back_to_general_for_unknown_values(): void
    {
        $this->assertSame('General', GrapesJsComponentCategoryNormalizer::normalize('Random group'));
        $this->assertSame('General', GrapesJsComponentCategoryNormalizer::normalize(null));
        $this->assertSame('General', GrapesJsComponentCategoryNormalizer::normalize(''));
    }
}
