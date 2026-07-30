<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\View;

/**
 * Article Channel.
 */
final class ArticleChannel
{
    public const LAYOUT_ARTICLE = 'article';

    public const LAYOUT_SECTION_INDEX = 'section_index';

    public static function activeThemeId(): string
    {
        return SubThemeResolver::forCurrentRoute();
    }

    /**
     * CSS / markup family used by bundled article themes (blog vs news magazine).
     */
    public static function presentationFamily(): string
    {
        return self::presentationFamilyFor(self::activeThemeId());
    }

    public static function presentationFamilyFor(string $themeId): string
    {
        if ($themeId === 'news') {
            return 'news';
        }

        $layout = app(SubThemeRegistry::class)->layout($themeId, self::LAYOUT_ARTICLE) ?? '';

        if (str_contains($layout, 'themes.news')) {
            return 'news';
        }

        if (
            str_contains($layout, 'themes.blog')
            || str_contains($layout, 'blog-custom')
            || str_contains($layout, '.blog.')
        ) {
            return 'blog';
        }

        return 'blog';
    }

    public static function shellClassPrefix(): string
    {
        return 'voodbuilder-'.self::presentationFamily();
    }

    public static function bodyClass(): string
    {
        return 'voodbuilder-sub-theme-'.self::presentationFamily().' voodbuilder-has-reading-progress';
    }

    public static function layoutView(string $layoutKey): string
    {
        $themeId = self::activeThemeId();
        $registry = app(SubThemeRegistry::class);
        $view = $registry->layout($themeId, $layoutKey);

        if ($view !== null) {
            return $view;
        }

        $fallback = $registry->layout('blog', $layoutKey);

        return $fallback ?? 'voodbuilder::themes.blog.layouts.'.$layoutKey;
    }

    public static function contentYieldName(string $layoutKey): string
    {
        return match ($layoutKey) {
            self::LAYOUT_SECTION_INDEX => 'section_index',
            self::LAYOUT_ARTICLE => 'page',
            default => 'page',
        };
    }

    public static function inkPartial(string $name): string
    {
        return self::inkPartialFor(self::presentationFamily(), $name);
    }

    public static function inkPartialFor(string $family, string $name): string
    {
        $candidate = "voodbuilder::article-channel.ink.{$family}.{$name}";

        if (View::exists($candidate)) {
            return $candidate;
        }

        return "voodbuilder::article-channel.ink.blog.{$name}";
    }
}
