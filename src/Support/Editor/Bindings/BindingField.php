<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

/**
 * Binding Field.
 */
final readonly class BindingField
{
    public const TYPE_TEXT = 'text';

    public const TYPE_URL = 'url';

    public const TYPE_IMAGE = 'image';

    /** Trusted HTML from companion packages (e.g. publication body). Sanitized on apply. */
    public const TYPE_HTML = 'html';

    public function __construct(
        public string $id,
        public string $label,
        public string $type = self::TYPE_TEXT,
        public ?string $group = null,
    ) {}

    /**
     * @return array{id: string, label: string, type: string, group?: string}
     */
    public function toArray(): array
    {
        $payload = [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
        ];

        if ($this->group !== null && $this->group !== '') {
            $payload['group'] = $this->group;
        }

        return $payload;
    }
}
