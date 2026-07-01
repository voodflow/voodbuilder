<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

final class GrapesJsBindingNormalizer
{
    public function __construct(
        private readonly BindingRegistry $registry,
    ) {}

    public function normalizeHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-bind')) {
            return $html;
        }

        return preg_replace_callback(
            '/\sdata-voodbuilder-bind=(["\'])(.*?)\1/i',
            function (array $matches): string {
                $quote = $matches[1];
                $key = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if (BindingKey::tryParse($key, $this->registry) === null) {
                    return $matches[0];
                }

                return ' data-voodbuilder-bind='.$quote.htmlspecialchars($key, ENT_QUOTES | ENT_HTML5, 'UTF-8').$quote;
            },
            $html,
        ) ?? $html;
    }
}
