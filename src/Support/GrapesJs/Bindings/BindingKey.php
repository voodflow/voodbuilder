<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

final readonly class BindingKey
{
    public function __construct(
        public string $sourceId,
        public string $fieldId,
    ) {}

    public function toAttribute(): string
    {
        return $this->sourceId.'.'.$this->fieldId;
    }

    public static function tryParse(string $raw, BindingRegistry $registry): ?self
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        foreach ($registry->sourceIdsByLengthDesc() as $sourceId) {
            $prefix = $sourceId.'.';

            if (! str_starts_with($raw, $prefix)) {
                continue;
            }

            $fieldId = substr($raw, strlen($prefix));

            if ($fieldId === '' || ! $registry->hasField($sourceId, $fieldId)) {
                return null;
            }

            return new self($sourceId, $fieldId);
        }

        return null;
    }
}
