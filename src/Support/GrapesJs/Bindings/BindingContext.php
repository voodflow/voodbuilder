<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

use Voodflow\Vpress\Models\SitePage;

final readonly class BindingContext
{
    public function __construct(
        public ?SitePage $page = null,
        public ?string $locale = null,
    ) {}

    public static function forPage(?SitePage $page): self
    {
        return new self(
            page: $page,
            locale: $page?->locale ?? app()->getLocale(),
        );
    }
}
