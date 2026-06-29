<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageHome;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageHomeTest extends TestCase
{
    public function test_assign_home_clears_other_pages_in_same_locale(): void
    {
        $groupA = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $groupB = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';

        $oldHome = SitePage::query()->create([
            'title' => 'Old EN',
            'slug' => 'old-en',
            'locale' => 'en',
            'translation_group_id' => $groupA,
            'content' => [],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        $newHome = SitePage::query()->create([
            'title' => 'New EN',
            'slug' => 'new-en',
            'locale' => 'en',
            'translation_group_id' => $groupB,
            'content' => [],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        SitePageHome::assignHome($newHome);

        $this->assertFalse($oldHome->fresh()->is_home);
        $this->assertTrue($newHome->fresh()->is_home);
    }

    public function test_assign_home_clears_other_translation_groups_in_other_locales(): void
    {
        $groupA = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
        $groupB = 'dddddddd-dddd-dddd-dddd-dddddddddddd';

        $oldEn = SitePage::query()->create([
            'title' => 'Old EN',
            'slug' => 'old-en-2',
            'locale' => 'en',
            'translation_group_id' => $groupA,
            'content' => [],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        $oldIt = SitePage::query()->create([
            'title' => 'Old IT',
            'slug' => 'old-it',
            'locale' => 'it',
            'translation_group_id' => $groupA,
            'content' => [],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        $newEn = SitePage::query()->create([
            'title' => 'New EN',
            'slug' => 'new-en-2',
            'locale' => 'en',
            'translation_group_id' => $groupB,
            'content' => [],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        SitePageHome::assignHome($newEn);

        $this->assertFalse($oldEn->fresh()->is_home);
        $this->assertFalse($oldIt->fresh()->is_home);
    }

    public function test_linked_translations_can_both_remain_home(): void
    {
        $group = 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee';

        $en = SitePage::query()->create([
            'title' => 'Home EN',
            'slug' => 'home-en-linked',
            'locale' => 'en',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        $it = SitePage::query()->create([
            'title' => 'Home IT',
            'slug' => 'home-it-linked',
            'locale' => 'it',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        SitePageHome::assignHome($it);

        $this->assertTrue($en->fresh()->is_home);
        $this->assertTrue($it->fresh()->is_home);
        $this->assertCount(0, SitePageHome::conflictingHomes($it));
    }
}
