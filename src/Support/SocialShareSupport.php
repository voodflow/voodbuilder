<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

final class SocialShareSupport
{
    /** @return array<string, string> */
    public static function networkOptions(): array
    {
        return [
            'facebook' => __('voodbuilder::landing.social.networks.facebook'),
            'x' => __('voodbuilder::landing.social.networks.x'),
            'linkedin' => __('voodbuilder::landing.social.networks.linkedin'),
            'whatsapp' => __('voodbuilder::landing.social.networks.whatsapp'),
            'email' => __('voodbuilder::landing.social.networks.email'),
            'copy_link' => __('voodbuilder::landing.social.networks.copy_link'),
        ];
    }

    /** @return list<string> */
    public static function defaultNetworks(): array
    {
        return ['facebook', 'x', 'linkedin', 'whatsapp', 'email', 'copy_link'];
    }

    public static function resolveShareUrl(?string $configuredUrl): string
    {
        if (filled($configuredUrl)) {
            return $configuredUrl;
        }

        return url()->current();
    }

    public static function resolveShareTitle(?string $configuredTitle): string
    {
        if (filled($configuredTitle)) {
            return $configuredTitle;
        }

        if (class_exists(VoodbuilderSettings::class)) {
            return VoodbuilderSettings::siteTitle();
        }

        return (string) config('app.name');
    }

    public static function shareUrlForNetwork(string $network, string $pageUrl, string $title): ?string
    {
        $encodedUrl = rawurlencode($pageUrl);
        $encodedTitle = rawurlencode($title);

        return match ($network) {
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.$encodedUrl,
            'x' => 'https://twitter.com/intent/tweet?url='.$encodedUrl.'&text='.$encodedTitle,
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url='.$encodedUrl,
            'whatsapp' => 'https://wa.me/?text='.$encodedTitle.'%20'.$encodedUrl,
            'email' => 'mailto:?subject='.$encodedTitle.'&body='.$encodedUrl,
            'copy_link' => null,
            default => null,
        };
    }

    /**
     * @param  list<string>|null  $networks
     * @return list<array{key: string, label: string, url: ?string}>
     */
    public static function links(?array $networks, string $pageUrl, string $title): array
    {
        $selected = filled($networks) ? $networks : self::defaultNetworks();
        $options = self::networkOptions();
        $links = [];

        foreach ($selected as $network) {
            if (! is_string($network) || ! array_key_exists($network, $options)) {
                continue;
            }

            $links[] = [
                'key' => $network,
                'label' => $options[$network],
                'url' => self::shareUrlForNetwork($network, $pageUrl, $title),
            ];
        }

        return $links;
    }
}
