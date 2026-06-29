<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

final readonly class BindingField
{
    public const TYPE_TEXT = 'text';

    public const TYPE_URL = 'url';

    public const TYPE_IMAGE = 'image';

    public function __construct(
        public string $id,
        public string $label,
        public string $type = self::TYPE_TEXT,
    ) {}

    /**
     * @return array{id: string, label: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
        ];
    }
}
