<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorHtmlSanitizer;
use Voodflow\Voodbuilder\Support\Editor\EditorTailwindConflictNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorTailwindConflictNormalizerTest extends TestCase
{
    public function test_a_later_text_color_replaces_an_earlier_one(): void
    {
        $this->assertSame(
            'inline-flex px-6 text-vp-brand-2',
            EditorTailwindConflictNormalizer::normalizeClassList(
                'inline-flex px-6 text-white text-vp-brand-2',
            ),
        );
    }

    public function test_a_later_background_color_replaces_an_earlier_one(): void
    {
        $this->assertSame(
            'rounded-lg bg-vp-divider',
            EditorTailwindConflictNormalizer::normalizeClassList('rounded-lg bg-vp-brand-1 bg-vp-divider'),
        );
    }

    public function test_colors_from_different_variants_are_kept(): void
    {
        $classes = 'text-white hover:text-vp-brand-2 dark:text-vp-text-1';

        $this->assertSame($classes, EditorTailwindConflictNormalizer::normalizeClassList($classes));
    }

    public function test_size_alignment_and_layout_utilities_are_not_treated_as_colors(): void
    {
        $classes = 'text-sm text-center text-xl text-left font-semibold';

        $this->assertSame($classes, EditorTailwindConflictNormalizer::normalizeClassList($classes));
    }

    public function test_arbitrary_values_are_only_treated_as_colors_when_they_are_colors(): void
    {
        $this->assertSame(
            'text-[14px] text-[#ff0000]',
            EditorTailwindConflictNormalizer::normalizeClassList('text-[#00ff00] text-[14px] text-[#ff0000]'),
        );
    }

    public function test_palette_shades_and_duplicates_are_normalized(): void
    {
        $this->assertSame(
            'text-sm text-zinc-200',
            EditorTailwindConflictNormalizer::normalizeClassList('text-sm text-white text-zinc-100 text-zinc-200'),
        );

        $this->assertSame('px-6 rounded-lg', EditorTailwindConflictNormalizer::normalizeClassList('px-6 px-6 rounded-lg'));
    }

    public function test_class_attributes_in_html_are_normalized(): void
    {
        $html = '<a class="inline-flex text-white hover:bg-vp-brand-2 bg-vp-divider text-vp-brand-2">INFORMAZIONI</a>';

        $this->assertSame(
            '<a class="inline-flex hover:bg-vp-brand-2 bg-vp-divider text-vp-brand-2">INFORMAZIONI</a>',
            EditorTailwindConflictNormalizer::normalizeHtml($html),
        );
    }

    public function test_sanitizer_applies_the_normalization(): void
    {
        $html = '<a href="#" class="rounded-lg bg-vp-brand-1 px-6 text-white bg-vp-divider text-vp-brand-2">Ciao</a>';

        $this->assertSame(
            '<a href="#" class="rounded-lg px-6 bg-vp-divider text-vp-brand-2">Ciao</a>',
            EditorHtmlSanitizer::sanitize($html),
        );
    }
}
