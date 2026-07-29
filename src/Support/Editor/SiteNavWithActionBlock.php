<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class SiteNavWithActionBlock extends AbstractSiteNavVariantBlock
{
    public static function variant(): string
    {
        return 'with_action';
    }
}
