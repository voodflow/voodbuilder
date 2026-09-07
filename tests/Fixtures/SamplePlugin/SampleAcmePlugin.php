<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Fixtures\SamplePlugin;

use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingField;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Minimal third-party style plugin used only in tests/docs — not a Cosmolab product.
 */
final class SampleAcmePlugin
{
    public static function register(): void
    {
        Voodbuilder::editorBlock(
            id: 'acme-hello',
            label: 'Acme Hello',
            category: 'Acme',
            content: '<div class="p-4" data-acme-hello="1">Hello from Acme</div>',
        );

        Voodbuilder::editorBindingSource(new class implements EditorBindingSource
        {
            public function id(): string
            {
                return 'acme.site';
            }

            public function label(): string
            {
                return 'Acme site';
            }

            public function package(): string
            {
                return 'acme';
            }

            public function packageLabel(): string
            {
                return 'Acme';
            }

            public function fields(): array
            {
                return [
                    new BindingField('tagline', 'Tagline', BindingField::TYPE_TEXT),
                ];
            }

            public function resolve(string $fieldId, BindingContext $context): ?string
            {
                return $fieldId === 'tagline' ? 'Acme tagline' : null;
            }
        });

        Voodbuilder::editorCondition('acme_feature_flag', static function (array $condition, $page): bool {
            return ($condition['value'] ?? null) === 'on';
        });
    }
}
