<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Menu Tabler Icons.
 */
final class MenuTablerIcons
{
    /** @var array<string, string> */
    private const PATHS = [
        'brand-facebook' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
        'brand-x' => 'M4 4l11.733 16h4.267l-11.733 -16z M4 20l6.768 -6.768m2.46 -2.46l7.772 -7.772',
        'brand-instagram' => 'M4 4m0 4a4 4 0 014 -4h8a4 4 0 014 4v8a4 4 0 01-4 4h-8a4 4 0 01-4 -4z M12 12m-3 0a3 3 0 106 0a3 3 0 10-6 0 M16.5 7.5l0 .01',
        'brand-linkedin' => 'M4 4m0 2a2 2 0 012 -2h12a2 2 0 012 2v12a2 2 0 01-2 2h-12a2 2 0 01-2 -2z M8 11l0 5 M8 8l0 .01 M12 16l0 -5 M16 16v-5a2 2 0 10 -4 0',
        'brand-youtube' => 'M2 8a4 4 0 014 -4h12a4 4 0 014 4v8a4 4 0 01-4 4h-12a4 4 0 01-4 -4v-8z M10 9l6 3l-6 3z',
        'brand-github' => 'M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 00-1.3 -3.2a4.2 4.2 0 00-.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 00-6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 00-.1 3.2a4.6 4.6 0 00-1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5',
        'brand-tiktok' => 'M9 12a4 4 0 104 4v-11a5 5 0 005 5',
        'brand-telegram' => 'M15 10l-4 4l6 6l4 -16l-18 7l4 2l2 6l3 -4',
        'brand-whatsapp' => 'M3 21l1.65 -3.8a9 9 0 113.4 2.9l-5.05 .9z M9 10a.5 .5 0 000 1h1a.5 .5 0 000 -1h-1z',
        'brand-discord' => 'M8 12a1 1 0 102 0a1 1 0 00-2 0 M14 12a1 1 0 102 0a1 1 0 00-2 0 M15.5 17c0 1 1.5 3 2 3c1.5 0 2.833 -1.667 3.5 -3c.667 -1.667 .5 -5.833 -1.5 -11.5c-1.457 -1.015 -3 -1.34 -4.5 -1.5l-1 2.5 M8.5 17c0 1 -1.356 3 -1.832 3c-1.429 0 -2.698 -1.667 -3.333 -3c-.635 -1.667 -.476 -5.833 1.428 -11.5c1.388 -1.015 2.857 -1.34 4.286 -1.5l1 2.5',
        'brand-pinterest' => 'M3 12a9 9 0 109 9a4.5 4.5 0 005 -5v-1.5a.6 .6 0 011 -.1a.5 .5 0 01.4 .2l.7 1.3a.6 .6 0 00.5 .3h2.4a1 1 0 001 -1v-7.3a9 9 0 00-18 0',
        'brand-threads' => 'M19 7.5c-1.333 -3 -3.667 -4.5 -7 -4.5c-5 0 -8 2.5 -8 9c0 6.5 3 9 8 9c4 0 6.5 -2 7.5 -6',
        'mail' => 'M3 7a2 2 0 012 -2h14a2 2 0 012 2v10a2 2 0 01-2 2h-14a2 2 0 01-2 -2v-10z M3 7l9 6l9 -6',
        'world' => 'M3 12a9 9 0 1018 0a9 9 0 00-18 0 M3.6 9h16.8 M3.6 15h16.8 M12 3a17 17 0 000 18 M12 3a17 17 0 010 18',
        'link' => 'M9 15l6 -6 M11 6l.463 -.536a5 5 0 017.072 0a4.993 4.993 0 011.193 5.435l-2.667 5.333 M13 18l-.397 .534a5.068 5.068 0 01-7.127 0a4.973 4.973 0 010 -7.071l2.667 -5.334',
    ];

    /** @return array<string, string> */
    public static function options(): array
    {
        $labels = [];

        foreach (array_keys(self::PATHS) as $name) {
            $labels[$name] = str($name)->replace('-', ' ')->title()->toString();
        }

        return $labels;
    }

    public static function has(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }

    public static function path(string $name): ?string
    {
        return self::PATHS[$name] ?? null;
    }
}
