<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Seo;

use RalphJSmit\Laravel\SEO\Support\LinkTag;

/**
 * Favicon link with optional `media` (prefers-color-scheme).
 */
final class MediaFaviconLinkTag extends LinkTag
{
    public function __construct(string $href, ?string $media = null, string $rel = 'icon')
    {
        parent::__construct($rel, $href);

        if (filled($media)) {
            $this->attributes['media'] = $media;
        }
    }
}
