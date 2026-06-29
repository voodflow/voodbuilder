<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class PageBuilderAccessTest extends TestCase
{
    public function test_guest_cannot_use_page_builder(): void
    {
        $this->assertFalse(PageBuilderAccess::userCanUsePageBuilder());
    }

    public function test_permission_name_comes_from_config(): void
    {
        config(['voodbuilder.permissions.page_builder' => 'custom_builder']);

        $this->assertSame('custom_builder', PageBuilderAccess::permissionName());
    }
}
