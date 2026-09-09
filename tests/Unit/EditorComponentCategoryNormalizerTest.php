<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorComponentCategoryNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorComponentCategoryNormalizerTest extends TestCase
{
    public function test_normalizes_case_insensitive_matches(): void
    {
        $this->assertSame('Hero', EditorComponentCategoryNormalizer::normalize('hero'));
        $this->assertSame('Hero', EditorComponentCategoryNormalizer::normalize('HERO'));
        $this->assertSame('Hero', EditorComponentCategoryNormalizer::normalize(' Hero '));
    }

    public function test_maps_known_aliases(): void
    {
        $this->assertSame('Content', EditorComponentCategoryNormalizer::normalize('Sections'));
        $this->assertSame('Content', EditorComponentCategoryNormalizer::normalize('section'));
        $this->assertSame('Features', EditorComponentCategoryNormalizer::normalize('feature'));
    }

    public function test_falls_back_to_general_for_unknown_values(): void
    {
        $this->assertSame('General', EditorComponentCategoryNormalizer::normalize('Random group'));
        $this->assertSame('General', EditorComponentCategoryNormalizer::normalize(null));
        $this->assertSame('General', EditorComponentCategoryNormalizer::normalize(''));
    }

    public function test_categories_are_sorted_alphabetically(): void
    {
        $categories = EditorComponentCategoryNormalizer::categories();
        $sorted = $categories;
        natcasesort($sorted);

        $this->assertSame(array_values($sorted), $categories);
        $this->assertContains('General', $categories);
    }
}
