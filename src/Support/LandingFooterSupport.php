<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

final class LandingFooterSupport
{
    /**
     * @param  array<string, mixed>  $config
     * @return array{logo_url: ?string, brand_name: ?string, lines: list<array{label: string, url: ?string, is_email: bool}>}
     */
    public static function organizerColumn(array $config): array
    {
        $logoUrl = filled($config['logo_url'] ?? null) ? (string) $config['logo_url'] : null;
        $brandName = filled($config['brand_name'] ?? null) ? (string) $config['brand_name'] : null;
        $lines = [];

        foreach (['organizer_line_1', 'organizer_line_2'] as $field) {
            if (filled($config[$field] ?? null)) {
                $lines[] = self::line((string) $config[$field]);
            }
        }

        if (filled($config['organizer_email'] ?? null)) {
            $email = (string) $config['organizer_email'];
            $lines[] = self::line($email, 'mailto:'.$email, true);
        }

        return [
            'logo_url' => $logoUrl,
            'brand_name' => $brandName,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{title: string, links: list<array{label: string, url: string, open_in_new_tab: bool}>}>
     */
    public static function menuColumns(array $config): array
    {
        $columns = [];

        foreach (($config['menu_columns'] ?? []) as $column) {
            if (! is_array($column) || blank($column['title'] ?? null)) {
                continue;
            }

            $links = [];

            foreach (($column['links'] ?? []) as $link) {
                if (! is_array($link) || blank($link['label'] ?? null) || blank($link['url'] ?? null)) {
                    continue;
                }

                $links[] = [
                    'label' => (string) $link['label'],
                    'url' => (string) $link['url'],
                    'open_in_new_tab' => (bool) ($link['open_in_new_tab'] ?? false),
                ];
            }

            $columns[] = [
                'title' => (string) $column['title'],
                'links' => $links,
            ];

            if (count($columns) >= 4) {
                break;
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{type: string, label: string, url: ?string, highlight: bool}>
     */
    public static function copyrightSegments(array $config): array
    {
        $segments = [];
        $year = filled($config['copyright_year'] ?? null)
            ? (string) $config['copyright_year']
            : (string) now()->year;

        if (filled($config['copyright_brand'] ?? null)) {
            $segments[] = [
                'type' => 'text',
                'label' => '© '.$year.' '.strtoupper((string) $config['copyright_brand']),
                'url' => null,
                'highlight' => false,
            ];
        }

        if (filled($config['copyright_claim'] ?? null)) {
            $segments[] = [
                'type' => 'text',
                'label' => strtoupper((string) $config['copyright_claim']),
                'url' => null,
                'highlight' => false,
            ];
        }

        if (filled($config['copyright_highlight'] ?? null)) {
            $segments[] = [
                'type' => 'text',
                'label' => strtoupper((string) $config['copyright_highlight']),
                'url' => null,
                'highlight' => true,
            ];
        }

        foreach (($config['copyright_links'] ?? []) as $link) {
            if (! is_array($link) || blank($link['label'] ?? null)) {
                continue;
            }

            $segments[] = [
                'type' => 'link',
                'label' => (string) $link['label'],
                'url' => filled($link['url'] ?? null) ? (string) $link['url'] : null,
                'highlight' => (bool) ($link['highlight'] ?? false),
            ];
        }

        return $segments;
    }

    /**
     * @return array{label: string, url: ?string, is_email: bool}
     */
    private static function line(string $label, ?string $url = null, bool $isEmail = false): array
    {
        return [
            'label' => $label,
            'url' => $url,
            'is_email' => $isEmail,
        ];
    }
}
