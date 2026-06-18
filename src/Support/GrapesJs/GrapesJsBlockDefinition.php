<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

final class GrapesJsBlockDefinition
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $category,
        public readonly string $content,
        public readonly array $attributes = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toEditorArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'category' => $this->category,
            'content' => $this->content,
            'attributes' => $this->attributes,
        ];
    }
}
