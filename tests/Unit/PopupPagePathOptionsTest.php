<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\Popups\PopupPagePathOptions;
use Voodflow\Voodbuilder\Tests\TestCase;

class PopupPagePathOptionsTest extends TestCase
{
    public function test_includes_all_pages_option(): void
    {
        $options = PopupPagePathOptions::all();

        $this->assertNotEmpty($options);
        $this->assertSame('', $options[0]['value']);
    }
}
