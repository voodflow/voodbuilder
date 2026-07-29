<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

final class BindingImageAltResolver
{
    /** @var list<string> */
    private const ALT_FIELD_CANDIDATES = [
        'title',
        'name',
        'label',
        'company_name',
        'slug',
    ];

    public function __construct(
        private readonly BindingRegistry $registry,
    ) {}

    public function resolve(string $bindingKey, BindingContext $context): ?string
    {
        $parsed = BindingKey::tryParse($bindingKey, $this->registry);

        if ($parsed === null) {
            return null;
        }

        $field = $this->registry->field($parsed->sourceId, $parsed->fieldId);

        if ($field === null || $field->type !== BindingField::TYPE_IMAGE) {
            return null;
        }

        foreach (self::ALT_FIELD_CANDIDATES as $candidateFieldId) {
            $candidateKey = $parsed->sourceId.'.'.$candidateFieldId;

            if (! $this->registry->hasField($parsed->sourceId, $candidateFieldId)) {
                continue;
            }

            $value = $this->registry->resolve($candidateKey, $context);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    public static function isPlaceholderAlt(string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '' || $trimmed === 'Dynamic image') {
            return true;
        }

        return (bool) preg_match('/^\[[^:]+:[^\]]+\]$/', $trimmed);
    }
}
