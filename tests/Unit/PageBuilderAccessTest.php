<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\PageBuilderAccess;
use Voodflow\Vpress\Tests\TestCase;

class PageBuilderAccessTest extends TestCase
{
    public function test_guest_cannot_use_page_builder(): void
    {
        $this->assertFalse(PageBuilderAccess::userCanUsePageBuilder());
    }

    public function test_permission_name_comes_from_config(): void
    {
        config(['vpress.permissions.page_builder' => 'custom_builder']);

        $this->assertSame('custom_builder', PageBuilderAccess::permissionName());
    }
}
