<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final class ProfileMenuLinkRegistry
{
    /** @var list<callable(): (array{label: string, url: string}|null)> */
    private static array $resolvers = [];

    /**
     * @param  callable(): (array{label: string, url: string}|null)  $resolver
     */
    public static function register(callable $resolver): void
    {
        self::$resolvers[] = $resolver;
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public static function links(): array
    {
        $links = [];

        foreach (self::$resolvers as $resolver) {
            try {
                $link = $resolver();
            } catch (\Throwable) {
                continue;
            }

            if (! is_array($link)) {
                continue;
            }

            $label = $link['label'] ?? null;
            $url = $link['url'] ?? null;

            if (! is_string($label) || $label === '' || ! is_string($url) || $url === '') {
                continue;
            }

            $links[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

        return $links;
    }

    public static function flush(): void
    {
        self::$resolvers = [];
    }
}
