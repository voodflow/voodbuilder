<?php

declare(strict_types=1);

namespace Voodflow\Vpress;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Vpress\Support\RichContentBlockRegistry;

class Vpress
{
    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public static function richContentBlock(string $group, string $blockClass): void
    {
        app(RichContentBlockRegistry::class)->register($group, $blockClass);
    }
}
