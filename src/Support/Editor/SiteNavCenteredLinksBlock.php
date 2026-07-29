<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class SiteNavCenteredLinksBlock extends AbstractSiteNavVariantBlock
{
    public static function variant(): string
    {
        return 'centered_links';
    }
}
