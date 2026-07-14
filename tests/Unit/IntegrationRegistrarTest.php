<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\IntegrationRegistrar;
use Voodflow\Voodbuilder\Tests\TestCase;

class IntegrationRegistrarTest extends TestCase
{
    public function test_boot_does_not_throw_when_integrations_file_is_missing(): void
    {
        IntegrationRegistrar::boot();

        $this->assertTrue(true);
    }
}
