<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Ensures GrapesJS form blocks use the shared vb-gjs-form styles on published pages.
 */
final class GrapesJsFormNormalizer
{
    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<form')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches): string {
                $attributes = $matches[1];

                if (! self::isManagedForm($attributes)) {
                    return $matches[0];
                }

                if (str_contains($attributes, 'vb-gjs-form')) {
                    return $matches[0];
                }

                if (preg_match('/\bclass=(["\'])(.*?)\1/i', $attributes, $classMatch)) {
                    $quote = $classMatch[1];
                    $classes = trim($classMatch[2].' vb-gjs-form');
                    $attributes = (string) preg_replace(
                        '/\bclass=(["\']).*?\1/i',
                        'class='.$quote.$classes.$quote,
                        $attributes,
                        1,
                    );

                    return '<form'.$attributes.'>';
                }

                return '<form class="vb-gjs-form"'.$attributes.'>';
            },
            $html,
        );
    }

    public static function normalizeForPage(string $html, SitePage $page): string
    {
        if ($html === '' || ! str_contains($html, '<form')) {
            return self::normalize($html);
        }

        $html = self::normalize($html);
        $action = route('voodbuilder.grapesjs.forms.submit', $page);
        $token = csrf_token();

        return (string) preg_replace_callback(
            '/<form\b([^>]*)>(.*?)<\/form>/is',
            static function (array $matches) use ($action, $token): string {
                $attributes = $matches[1];
                $inner = $matches[2];

                if (! self::isManagedForm($attributes)) {
                    return $matches[0];
                }

                $attributes = self::removeAttribute($attributes, 'onsubmit');
                $attributes = self::setAttribute($attributes, 'action', $action);
                $attributes = self::setAttribute($attributes, 'method', 'post');

                if (self::isNewsletterForm($attributes)) {
                    $inner = self::ensureHiddenInput($inner, 'form_type', 'newsletter');
                    $inner = self::ensureNewsletterListField($attributes, $inner);
                }

                if (filled($token)) {
                    $inner = self::ensureHiddenInput($inner, '_token', (string) $token);
                }

                return '<form'.$attributes.'>'.$inner.'</form>';
            },
            $html,
        );
    }

    protected static function isManagedForm(string $attributes): bool
    {
        return str_contains($attributes, 'vb-gjs-form')
            || preg_match('/\bdata-voodbuilder-form\s*=\s*["\'](?:newsletter|contact)["\']/', $attributes) === 1;
    }

    protected static function isNewsletterForm(string $attributes): bool
    {
        return str_contains($attributes, 'vb-gjs-newsletter-form')
            || preg_match('/\bdata-voodbuilder-form\s*=\s*["\']newsletter["\']/', $attributes) === 1;
    }

    protected static function ensureNewsletterListField(string $attributes, string $inner): string
    {
        if (! preg_match('/\bdata-voodbuilder-newsletter-list\s*=\s*["\']([^"\']*)["\']/', $attributes, $matches)) {
            return $inner;
        }

        $list = trim($matches[1]);

        if ($list === '') {
            return $inner;
        }

        return self::ensureHiddenInput($inner, 'newsletter_list', $list);
    }

    protected static function ensureHiddenInput(string $inner, string $name, string $value): string
    {
        if (preg_match('/<input\b[^>]*\bname=(["\'])'.preg_quote($name, '/').'\1/i', $inner) === 1) {
            return $inner;
        }

        $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="'.$name.'" value="'.$escapedValue.'">'.$inner;
    }

    protected static function setAttribute(string $attributes, string $name, string $value): string
    {
        $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $replacement = $name.'="'.$escapedValue.'"';

        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*(["\']).*?\1/i', $attributes) === 1) {
            return (string) preg_replace(
                '/\b'.preg_quote($name, '/').'\s*=\s*(["\']).*?\1/i',
                $replacement,
                $attributes,
                1,
            );
        }

        return trim($attributes).' '.$replacement;
    }

    protected static function removeAttribute(string $attributes, string $name): string
    {
        return trim((string) preg_replace('/\s*\b'.preg_quote($name, '/').'\s*=\s*(["\']).*?\1/i', '', $attributes));
    }
}
