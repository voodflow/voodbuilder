<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\TailwindV4ClassMigrator;
use Voodflow\Voodbuilder\Tests\TestCase;

class TailwindV4ClassMigratorTest extends TestCase
{
    public function test_migrates_deprecated_tailwind_utilities(): void
    {
        $html = '<div class="flex-grow flex-shrink-0 overflow-ellipsis decoration-clone"></div>';

        $migrated = TailwindV4ClassMigrator::migrateHtml($html);

        $this->assertStringContainsString('grow', $migrated);
        $this->assertStringContainsString('shrink-0', $migrated);
        $this->assertStringContainsString('text-ellipsis', $migrated);
        $this->assertStringContainsString('box-decoration-clone', $migrated);
        $this->assertStringNotContainsString('flex-grow', $migrated);
        $this->assertStringNotContainsString('flex-shrink-0', $migrated);
    }
}
