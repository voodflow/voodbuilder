<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsComponentInstanceCssScoper
{
    public const string SCOPE_ATTR = 'data-vb-component-id';

    public static function scopeCssToComponentInstance(string $css, string $componentId): string
    {
        if ($css === '' || $componentId === '') {
            return $css;
        }

        $scope = '['.self::SCOPE_ATTR.'="'.self::escapeAttributeValue($componentId).'"]';

        if (str_contains($css, $scope)) {
            return $css;
        }

        $css = preg_replace(
            '/:where\(\.voodbuilder-gjs-component-instance,\s*\.voodbuilder-component-rendered,\s*\.VPRichPage\)\s*:where\(\.voodbuilder-pasted-component\)/',
            $scope.' .voodbuilder-pasted-component',
            $css,
        ) ?? $css;

        $css = preg_replace(
            '/\.dark\s+:where\(\.voodbuilder-gjs-component-instance,\s*\.voodbuilder-component-rendered,\s*\.VPRichPage\)\s*:where\(\.voodbuilder-pasted-component\)/',
            '.dark '.$scope.' .voodbuilder-pasted-component',
            $css,
        ) ?? $css;

        foreach ([
            '.voodbuilder-gjs-component-instance .voodbuilder-pasted-component',
            '.voodbuilder-component-rendered .voodbuilder-pasted-component',
            '.VPRichPage .voodbuilder-pasted-component',
        ] as $legacySelector) {
            $css = str_replace($legacySelector, $scope.' .voodbuilder-pasted-component', $css);
        }

        return self::prefixUnscopedPastedComponentSelectors($css, $scope);
    }

    protected static function prefixUnscopedPastedComponentSelectors(string $css, string $scope): string
    {
        $needle = '.voodbuilder-pasted-component';
        $result = '';
        $offset = 0;

        while (($position = strpos($css, $needle, $offset)) !== false) {
            $prefix = substr($css, max(0, $position - 96), min(96, $position));

            if (preg_match('/\[data-vb-component-id="[^"]*"\]\s*$/', $prefix) === 1) {
                $result .= substr($css, $offset, $position - $offset + strlen($needle));
                $offset = $position + strlen($needle);

                continue;
            }

            $result .= substr($css, $offset, $position - $offset).$scope.' '.$needle;
            $offset = $position + strlen($needle);
        }

        return $result.substr($css, $offset);
    }

    protected static function escapeAttributeValue(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
