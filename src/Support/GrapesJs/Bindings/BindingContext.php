<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

use Voodflow\Vpress\Models\SitePage;

final readonly class BindingContext
{
    public function __construct(
        public ?SitePage $page = null,
        public ?string $locale = null,
        public mixed $repeatItem = null,
    ) {}

    public static function forPage(?SitePage $page, mixed $repeatItem = null): self
    {
        return new self(
            page: $page,
            locale: $page?->locale ?? app()->getLocale(),
            repeatItem: $repeatItem,
        );
    }

    public function withRepeatItem(mixed $repeatItem): self
    {
        return new self(
            page: $this->page,
            locale: $this->locale,
            repeatItem: $repeatItem,
        );
    }
}
