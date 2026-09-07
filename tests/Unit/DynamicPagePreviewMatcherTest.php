<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPagePreviewMatcher;

class DynamicPagePreviewMatcherTest extends TestCase
{
    public function test_builds_slug_candidates_from_page_slug_and_title(): void
    {
        $page = new SitePage([
            'slug' => 'exhibitor-profile',
            'title' => 'Yamaha',
        ]);

        $this->assertSame(
            ['exhibitor-profile', 'yamaha'],
            DynamicPagePreviewMatcher::slugCandidates($page),
        );
    }

    public function test_title_candidate_returns_trimmed_title(): void
    {
        $page = new SitePage(['title' => '  Yamaha  ']);

        $this->assertSame('Yamaha', DynamicPagePreviewMatcher::titleCandidate($page));
    }
}
