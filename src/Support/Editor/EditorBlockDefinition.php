<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class EditorBlockDefinition
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $category,
        public readonly string $content,
        public readonly ?string $preview = null,
        public readonly array $attributes = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toEditorArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'label' => $this->label,
            'category' => $this->category,
            'content' => EditorRichContentBlockAdapter::prepareBlockHtml($this->content),
            'preview' => $this->preview,
            'attributes' => $this->attributes,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
