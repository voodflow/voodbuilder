<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Events;

use Voodflow\Voodbuilder\Models\SitePage;

final class GrapesJsFormSubmitted
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly SitePage $page,
        public readonly array $payload,
    ) {}
}
