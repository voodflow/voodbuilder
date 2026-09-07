<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Publish-time cleanup for full-bleed YouTube/Vimeo hero backgrounds.
 *
 * GrapesJS / inline-video utilities can leave letterbox styles and omit mute,
 * which flash provider chrome on load — especially on tall mobile heroes.
 */
final class EditorHeroBackgroundNormalizer
{
    private const STRIP_CLASSES = [
        'w-full',
        'aspect-video',
        'rounded',
        'vb-video-host',
        'voodbuilder-hero-media__video',
    ];

    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'voodbuilder-hero-media')) {
            return $html;
        }

        // Wrap a direct-child iframe that is missing the cover embed host.
        $html = (string) preg_replace_callback(
            '/(<div\b[^>]*\bvoodbuilder-hero-media\b[^>]*>)(\s*)(<iframe\b[^>]*(?:\/>|>[\s\S]*?<\/iframe>))(\s*)(<\/div>)/i',
            static function (array $matches): string {
                if (str_contains($matches[0], 'voodbuilder-hero-media__embed') || str_contains($matches[0], 'data-vb-embed-bg')) {
                    return $matches[0];
                }

                return $matches[1]
                    .$matches[2]
                    .'<div class="voodbuilder-hero-media__embed" data-vb-embed-bg="" aria-hidden="true">'
                    .$matches[3]
                    .'</div>'
                    .$matches[4]
                    .$matches[5];
            },
            $html,
        );

        $normalized = preg_replace_callback(
            '/<iframe\b[^>]*>/i',
            static function (array $matches) use ($html): string {
                $tag = $matches[0];

                if (! self::isHeroBackgroundIframe($tag, $html)) {
                    return $tag;
                }

                return self::normalizeIframeTag($tag);
            },
            $html,
        );

        return is_string($normalized) ? $normalized : $html;
    }

    private static function isHeroBackgroundIframe(string $tag, string $html): bool
    {
        if (str_contains($tag, 'voodbuilder-hero-media__iframe')
            || str_contains($tag, 'voodbuilder-hero-media__video')
            || str_contains($tag, 'data-vb-embed-bg')) {
            return true;
        }

        $offset = strpos($html, $tag);

        if ($offset === false) {
            return false;
        }

        $before = substr($html, max(0, $offset - 800), min(800, $offset));

        return str_contains($before, 'voodbuilder-hero-media__embed')
            || str_contains($before, 'data-vb-embed-bg')
            || (
                str_contains($before, 'voodbuilder-hero-media')
                && (str_contains($tag, 'youtube.com') || str_contains($tag, 'youtube-nocookie.com') || str_contains($tag, 'vimeo.com') || str_contains($tag, 'vb-video-host'))
            );
    }

    private static function normalizeIframeTag(string $tag): string
    {
        if (! preg_match('/\bsrc=(["\'])(.*?)\1/i', $tag, $srcMatch)) {
            return $tag;
        }

        $quote = $srcMatch[1];
        $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5);
        $hardened = self::hardenEmbedSrc($src);

        $tag = preg_replace(
            '/\bsrc=(["\']).*?\1/i',
            'src='.$quote.htmlspecialchars($hardened, ENT_QUOTES | ENT_HTML5).$quote,
            $tag,
            1,
        ) ?? $tag;

        if (preg_match('/\bclass=(["\'])(.*?)\1/i', $tag, $classMatch)) {
            $classes = preg_split('/\s+/', trim($classMatch[2])) ?: [];
            $classes = array_values(array_filter(
                $classes,
                static fn (string $class): bool => $class !== '' && ! in_array($class, self::STRIP_CLASSES, true),
            ));

            if (! in_array('voodbuilder-hero-media__iframe', $classes, true)) {
                $classes[] = 'voodbuilder-hero-media__iframe';
            }

            $tag = preg_replace(
                '/\bclass=(["\']).*?\1/i',
                'class='.$classMatch[1].implode(' ', $classes).$classMatch[1],
                $tag,
                1,
            ) ?? $tag;
        } elseif (! str_contains($tag, 'voodbuilder-hero-media__iframe')) {
            $tag = rtrim(substr($tag, 0, -1)).' class="voodbuilder-hero-media__iframe">';
        }

        $tag = preg_replace('/\sstyle=(["\'])(.*?)\1/i', '', $tag) ?? $tag;
        $tag = preg_replace('/\sallowfullscreen(?:\s*=\s*(["\'])allowfullscreen\1|=(["\'])true\2|=(["\'])\3)?/i', '', $tag) ?? $tag;

        if (preg_match('/\bloading=(["\'])(.*?)\1/i', $tag)) {
            $tag = preg_replace('/\bloading=(["\']).*?\1/i', 'loading=$1eager$1', $tag) ?? $tag;
        } else {
            $tag = rtrim(substr($tag, 0, -1)).' loading="eager">';
        }

        if (! preg_match('/\btabindex=/i', $tag)) {
            $tag = rtrim(substr($tag, 0, -1)).' tabindex="-1">';
        }

        if (! preg_match('/\binert\b/i', $tag)) {
            $tag = rtrim(substr($tag, 0, -1)).' inert="">';
        }

        return $tag;
    }

    public static function hardenEmbedSrc(string $src): string
    {
        $src = trim($src);

        if ($src === '' || ! filter_var($src, FILTER_VALIDATE_URL)) {
            return $src;
        }

        $parts = parse_url($src);

        if (! is_array($parts) || ! isset($parts['host'], $parts['scheme'])) {
            return $src;
        }

        $host = strtolower((string) $parts['host']);
        parse_str((string) ($parts['query'] ?? ''), $query);

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtube-nocookie.com')) {
            $query['controls'] = '0';
            $query['mute'] = '1';
            $query['autoplay'] = $query['autoplay'] ?? '1';
            $query['playsinline'] = '1';
            $query['rel'] = '0';
            $query['modestbranding'] = '1';
            $query['iv_load_policy'] = '3';
            $query['fs'] = '0';
            $query['disablekb'] = '1';
            $query['enablejsapi'] = '1';
            unset($query['showinfo']);

            if (($query['loop'] ?? null) === '1') {
                $path = trim((string) ($parts['path'] ?? ''), '/');
                $segments = $path === '' ? [] : explode('/', $path);
                $id = $segments === [] ? '' : (string) end($segments);

                if ($id !== '' && blank($query['playlist'] ?? null)) {
                    $query['playlist'] = $id;
                }
            }
        } elseif (str_contains($host, 'vimeo.com')) {
            $query['background'] = '1';
            $query['controls'] = '0';
            $query['muted'] = '1';
            $query['autoplay'] = $query['autoplay'] ?? '1';
            $query['loop'] = $query['loop'] ?? '1';
            $query['title'] = '0';
            $query['byline'] = '0';
            $query['portrait'] = '0';
            $query['badge'] = '0';
        } else {
            return $src;
        }

        $rebuilt = $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '');

        $qs = http_build_query($query);

        return $qs !== '' ? $rebuilt.'?'.$qs : $rebuilt;
    }
}
