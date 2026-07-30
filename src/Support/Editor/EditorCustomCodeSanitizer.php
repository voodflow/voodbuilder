<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Editor Custom Code Sanitizer.
 */
final class EditorCustomCodeSanitizer
{
    /**
     * Strip executable content from grapesjs-custom-code output before persistence.
     */
    public static function sanitize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;
        $html = preg_replace('/\shref\s*=\s*(["\'])\s*javascript:.*?\1/i', ' href="#"', $html) ?? $html;
        $html = preg_replace('/\ssrc\s*=\s*(["\'])\s*javascript:.*?\1/i', '', $html) ?? $html;

        return $html;
    }
}
