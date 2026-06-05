<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use RalphJSmit\Laravel\SEO\SchemaCollection;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Vpress\Models\VpressSettings;

final class VpressSeo
{
    public static function applyDefaults(SEOData $seoData): SEOData
    {
        $settings = VpressSettings::data();

        if (blank($seoData->description) && filled($settings['seo_default_description'] ?? null)) {
            $seoData->description = (string) $settings['seo_default_description'];
        }

        if (blank($seoData->image) && filled($settings['seo_default_image'] ?? null)) {
            $seoData->image = VpressSettings::assetUrl('seo_default_image');
        }

        if (blank($seoData->site_name) && filled($settings['seo_site_name'] ?? null)) {
            $seoData->site_name = (string) $settings['seo_site_name'];
        } elseif (blank($seoData->site_name)) {
            $seoData->site_name = VpressSettings::siteTitle();
        }

        if (blank($seoData->favicon) && filled($settings['favicon'] ?? null)) {
            $seoData->favicon = VpressSettings::assetUrl('favicon');
        }

        if (blank($seoData->twitter_username) && filled($settings['seo_twitter_username'] ?? null)) {
            $seoData->twitter_username = (string) $settings['seo_twitter_username'];
        }

        if (blank($seoData->author) && filled($settings['seo_default_author'] ?? null)) {
            $seoData->author = (string) $settings['seo_default_author'];
        }

        if (blank($seoData->robots) && filled($settings['seo_robots'] ?? null)) {
            $seoData->robots = (string) $settings['seo_robots'];
        }

        if (($settings['seo_canonical_enabled'] ?? true) === false) {
            $seoData->canonical_url = null;
        } elseif (blank($seoData->canonical_url)) {
            $seoData->canonical_url = url()->current();
        }

        if (filled($settings['seo_title_suffix'] ?? null) && $seoData->enableTitleSuffix) {
            $suffix = (string) $settings['seo_title_suffix'];

            if ($seoData->title && ! str_ends_with($seoData->title, $suffix)) {
                $seoData->title .= $suffix;
            }

            if ($seoData->openGraphTitle && ! str_ends_with($seoData->openGraphTitle, $suffix)) {
                $seoData->openGraphTitle .= $suffix;
            }
        }

        $seoData->schema = static::mergeOrganizationSchema($seoData);

        return $seoData;
    }

    protected static function mergeOrganizationSchema(SEOData $seoData): ?SchemaCollection
    {
        $settings = VpressSettings::data();
        $organizationName = $settings['geo_organization_name'] ?? null;
        $organizationLogo = VpressSettings::assetUrl('geo_organization_logo');
        $siteSummary = $settings['geo_site_summary'] ?? $settings['seo_default_description'] ?? null;

        if (blank($organizationName) && blank($organizationLogo) && blank($siteSummary)) {
            return $seoData->schema;
        }

        $schema = $seoData->schema ?? SchemaCollection::initialize();

        $organization = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $organizationName ?: VpressSettings::siteTitle(),
            'url' => url('/'),
            'logo' => $organizationLogo,
            'description' => $siteSummary,
        ]);

        $website = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $settings['seo_site_name'] ?? VpressSettings::siteTitle(),
            'url' => url('/'),
            'description' => $siteSummary,
        ]);

        $schema->push(fn (): array => $organization);
        $schema->push(fn (): array => $website);

        return $schema;
    }

    /**
     * @return array<string, string|null>
     */
    public static function geoMetaTags(): array
    {
        $settings = VpressSettings::data();

        return array_filter([
            'geo.region' => $settings['geo_region'] ?? null,
            'geo.placename' => $settings['geo_placename'] ?? null,
            'abstract' => $settings['geo_site_summary'] ?? null,
            'ai:description' => $settings['geo_site_summary'] ?? null,
        ]);
    }
}
