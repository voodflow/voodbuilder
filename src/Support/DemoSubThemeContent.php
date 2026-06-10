<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vtuts\Support\Locales;

final class DemoSubThemeContent
{
    /** @return list<array{slug: string, key: string, days_ago: int}> */
    public static function blogPostDefinitions(): array
    {
        return [
            ['slug' => 'blog-welcome', 'key' => 'welcome', 'days_ago' => 1],
            ['slug' => 'blog-shipping-shell', 'key' => 'shipping-shell', 'days_ago' => 3],
            ['slug' => 'blog-sub-themes', 'key' => 'sub-themes', 'days_ago' => 5],
            ['slug' => 'blog-content-blocks', 'key' => 'content-blocks', 'days_ago' => 8],
            ['slug' => 'blog-pairing-vtuts', 'key' => 'pairing-vtuts', 'days_ago' => 12],
        ];
    }

    /** @return list<array{slug: string, key: string, days_ago: int}> */
    public static function newsArticleDefinitions(): array
    {
        return [
            ['slug' => 'news-morning-briefing', 'key' => 'briefing', 'days_ago' => 0],
            ['slug' => 'news-sub-themes-release', 'key' => 'release', 'days_ago' => 2],
            ['slug' => 'news-editorial-workflow', 'key' => 'workflow', 'days_ago' => 4],
            ['slug' => 'news-community-notes', 'key' => 'community', 'days_ago' => 6],
            ['slug' => 'news-roadmap', 'key' => 'roadmap', 'days_ago' => 9],
        ];
    }

    /** @return array{title: string, excerpt: string} */
    public static function blogIndexMeta(?string $locale = null): array
    {
        return static::withLocale($locale, fn (): array => [
            'title' => __('vpress::demo.blog.index_title'),
            'excerpt' => __('vpress::demo.blog.index_excerpt'),
        ]);
    }

    /** @return array{title: string, excerpt: string} */
    public static function newsIndexMeta(?string $locale = null): array
    {
        return static::withLocale($locale, fn (): array => [
            'title' => __('vpress::demo.news.index_title'),
            'excerpt' => __('vpress::demo.news.index_excerpt'),
        ]);
    }

    /** @return array{title: string, excerpt: string, content: array<string, mixed>} */
    public static function blogPost(string $key, ?string $locale = null): array
    {
        return static::withLocale($locale, function () use ($key): array {
            $prefix = "vpress::demo.blog.posts.{$key}";

            return [
                'title' => __($prefix.'.title'),
                'excerpt' => __($prefix.'.excerpt'),
                'content' => static::articleDocument([
                    ['h1', __($prefix.'.title')],
                    ['p', __($prefix.'.p1')],
                    ['p', __($prefix.'.p2')],
                ]),
            ];
        });
    }

    /** @return array{title: string, excerpt: string, content: array<string, mixed>} */
    public static function newsArticle(string $key, ?string $locale = null): array
    {
        return static::withLocale($locale, function () use ($key): array {
            $prefix = "vpress::demo.news.articles.{$key}";

            return [
                'title' => __($prefix.'.title'),
                'excerpt' => __($prefix.'.excerpt'),
                'content' => static::articleDocument([
                    ['h1', __($prefix.'.title')],
                    ['p', __($prefix.'.p1')],
                    ['p', __($prefix.'.p2')],
                ]),
            ];
        });
    }

    /**
     * @param  list<array{0: string, 1: string}>  $blocks
     * @return array<string, mixed>
     */
    private static function articleDocument(array $blocks): array
    {
        $content = [];

        foreach ($blocks as [$type, $text]) {
            $content[] = match ($type) {
                'h1' => static::heading(1, $text),
                'h2' => static::heading(2, $text),
                'h3' => static::heading(3, $text),
                'blockquote' => static::blockquote($text),
                default => static::paragraph($text),
            };
        }

        return [
            'type' => 'doc',
            'content' => $content,
        ];
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private static function withLocale(?string $locale, callable $callback): mixed
    {
        $locale ??= class_exists(Locales::class)
            ? Locales::default()
            : app()->getLocale();

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        $result = $callback();

        app()->setLocale($previousLocale);

        return $result;
    }

    /** @return array<string, mixed> */
    private static function heading(int $level, string $text): array
    {
        return [
            'type' => 'heading',
            'attrs' => ['level' => $level],
            'content' => [
                ['type' => 'text', 'text' => $text],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function paragraph(string $text): array
    {
        return [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => $text],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function blockquote(string $text): array
    {
        return [
            'type' => 'blockquote',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
        ];
    }
}
