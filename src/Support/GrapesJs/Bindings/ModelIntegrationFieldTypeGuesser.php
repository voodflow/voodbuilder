<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

final class ModelIntegrationFieldTypeGuesser
{
    public static function guess(string $fieldId): string
    {
        $normalized = strtolower($fieldId);

        if (preg_match('/(?:^|_)(?:url|link|href|slug|permalink)(?:$|_)/', $normalized)) {
            return BindingField::TYPE_URL;
        }

        if (preg_match('/(?:^|_)(?:image|photo|avatar|thumbnail|cover|picture|banner)(?:$|_)/', $normalized)) {
            return BindingField::TYPE_IMAGE;
        }

        return BindingField::TYPE_TEXT;
    }
}
