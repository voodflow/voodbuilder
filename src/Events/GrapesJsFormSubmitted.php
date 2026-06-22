<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Events;

use Voodflow\Vpress\Models\SitePage;

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
