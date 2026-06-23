<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Support\VpressLandingBlocks;
use Voodflow\Vpress\Vpress;

final class VpressLandingGrapesJsBlocks
{
    public static function register(): void
    {
        $category = 'Vpress / Landing';

        foreach (VpressLandingBlocks::blockClasses() as $blockClass) {
            Vpress::grapesJsRichContentBlock($category, $blockClass);
        }
    }
}
