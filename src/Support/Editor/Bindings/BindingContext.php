<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Binding Context.
 */
final readonly class BindingContext
{
    public function __construct(
        public ?SitePage $page = null,
        public ?string $locale = null,
        public mixed $repeatItem = null,
        public bool $editorPreview = false,
    ) {}

    public static function forPage(?SitePage $page, mixed $repeatItem = null): self
    {
        return new self(
            page: $page,
            locale: $page?->locale ?? app()->getLocale(),
            repeatItem: $repeatItem,
        );
    }

    public static function forEditorPreview(SitePage $page): self
    {
        return new self(
            page: $page,
            locale: $page->locale ?? app()->getLocale(),
            repeatItem: null,
            editorPreview: true,
        );
    }

    public function withRepeatItem(mixed $repeatItem): self
    {
        return new self(
            page: $this->page,
            locale: $this->locale,
            repeatItem: $repeatItem,
            editorPreview: $this->editorPreview,
        );
    }
}
