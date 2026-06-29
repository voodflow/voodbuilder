<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final class YoutubeEmbed
{
    public static function normalize(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('/^[\w-]{11}$/', $url) === 1) {
            return self::embedUrl($url);
        }

        if (str_contains($url, 'youtube.com/embed/')) {
            $videoId = self::extractVideoId($url);

            return $videoId !== null ? self::embedUrl($videoId) : $url;
        }

        $videoId = self::extractVideoId($url);

        if ($videoId === null) {
            return null;
        }

        return self::embedUrl($videoId);
    }

    private static function embedUrl(string $videoId): string
    {
        return 'https://www.youtube.com/embed/'.$videoId.'?rel=0';
    }

    private static function extractVideoId(string $url): ?string
    {
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~i', $url, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('~[?&]v=([\w-]{11})~', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
