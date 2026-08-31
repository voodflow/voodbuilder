<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Voodflow\Voodbuilder\Database\Seeders\VoodflowMarketingSiteSeeder;

class SeedMarketingSiteCommand extends Command
{
    protected $signature = 'voodbuilder:seed-marketing-site';

    protected $description = 'Seed the local Voodflow marketing site pages (slug prefix a)';

    public function handle(VoodflowMarketingSiteSeeder $seeder): int
    {
        $seeder->run();

        $this->components->info('Marketing site seeded. Open /pages/a in the browser.');

        return self::SUCCESS;
    }
}
