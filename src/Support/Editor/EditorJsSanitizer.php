<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class EditorJsSanitizer
{
    /**
     * Strip obviously dangerous patterns from Editor component scripts before persistence.
     */
    public static function sanitize(string $js): string
    {
        if ($js === '') {
            return $js;
        }

        $blocked = [
            '/<script\b/i',
            '/<\/script>/i',
            '/\beval\s*\(/i',
            '/\bnew\s+Function\s*\(/i',
            '/\bimport\s*\(/i',
            '/\bfetch\s*\(/i',
            '/\bXMLHttpRequest\b/i',
            '/document\.cookie/i',
            '/localStorage/i',
            '/sessionStorage/i',
        ];

        foreach ($blocked as $pattern) {
            if (preg_match($pattern, $js) === 1) {
                return '';
            }
        }

        return trim($js);
    }
}
