<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\SearchExcerpt;
use Voodflow\Voodbuilder\Tests\TestCase;

class SearchExcerptTest extends TestCase
{
    #[Test]
    public function it_strips_markdown_noise_from_snippets(): void
    {
        $markdown = <<<'MD'
### Overview

When **VoodBuilder** is installed, popups reuse the same canvas.

- Load
- Delay

[Read more](/docs/vpopups)
MD;

        $excerpt = SearchExcerpt::fromMarkdown($markdown, 120);

        $this->assertNotNull($excerpt);
        $this->assertStringNotContainsString('###', $excerpt);
        $this->assertStringNotContainsString('**', $excerpt);
        $this->assertStringNotContainsString('[Read more]', $excerpt);
        $this->assertStringContainsString('Overview', $excerpt);
        $this->assertStringContainsString('VoodBuilder', $excerpt);
    }

    #[Test]
    public function it_centers_snippet_around_the_match(): void
    {
        $plain = str_repeat('a ', 40).'unique-token '.str_repeat('b ', 40);
        $snippet = SearchExcerpt::aroundMatch($plain, 'unique-token', 80);

        $this->assertNotNull($snippet);
        $this->assertStringContainsString('unique-token', $snippet);
        $this->assertStringContainsString('…', $snippet);
    }

    #[Test]
    public function it_highlights_matches_safely(): void
    {
        $html = SearchExcerpt::highlight('Hello <script> VoodBuilder world', 'VoodBuilder');

        $this->assertStringContainsString('class="vb-search-mark"', $html);
        $this->assertStringContainsString('VoodBuilder</mark>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    #[Test]
    public function it_scores_title_matches_higher_than_body(): void
    {
        $titleScore = SearchExcerpt::score('blocks', 'Editor blocks', null, 'Something else');
        $bodyScore = SearchExcerpt::score('blocks', 'Getting started', null, 'Learn about blocks here');

        $this->assertGreaterThan($bodyScore, $titleScore);
    }

    #[Test]
    public function it_presents_preferred_excerpt_when_it_contains_the_term(): void
    {
        $presented = SearchExcerpt::present(
            'canvas',
            ['Short intro about the canvas API.'],
            str_repeat('noise ', 50).'canvas appears later '.str_repeat('x ', 50),
            100,
        );

        $this->assertNotNull($presented['excerpt']);
        $this->assertStringContainsString('canvas', (string) $presented['excerpt']);
        $this->assertStringContainsString('<mark', (string) $presented['excerpt_html']);
    }
}
