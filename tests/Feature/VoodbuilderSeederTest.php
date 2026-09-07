<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Database\Seeders\VoodbuilderSeeder;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderSeederTest extends TestCase
{
    public function test_default_seed_does_not_create_sample_pages(): void
    {
        config(['voodbuilder.seed.sample_pages' => false]);

        $this->seed(VoodbuilderSeeder::class);

        $this->assertSame(0, SitePage::query()->count());
        $this->assertNotNull(NavigationMenu::query()->where('slug', 'main')->first());
    }

    public function test_opt_in_sample_pages_creates_home_and_policies(): void
    {
        config(['voodbuilder.seed.sample_pages' => true]);

        $this->seed(VoodbuilderSeeder::class);

        $this->assertTrue(SitePage::query()->where('slug', 'home')->where('is_home', true)->exists());
        $this->assertTrue(SitePage::query()->where('slug', 'privacy-policy')->exists());
        $this->assertTrue(SitePage::query()->where('slug', 'cookie-policy')->exists());
    }
}
