<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\SchemaCollection;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\TagCollection;
use RalphJSmit\Laravel\SEO\Tags\FaviconTag;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\Seo\MediaFaviconLinkTag;

/**
 * Voodbuilder Seo.
 */
final class VoodbuilderSeo
{
    public static function applyDefaults(SEOData $seoData): SEOData
    {
        $settings = VoodbuilderSettings::data();

        if (self::shouldReplaceTitle($seoData->title)) {
            $seoData->title = VoodbuilderSettings::siteTitle();
        }

        if (blank($seoData->description) && filled($settings['seo_default_description'] ?? null)) {
            $seoData->description = (string) $settings['seo_default_description'];
        }

        if (blank($seoData->image) && filled($settings['seo_default_image'] ?? null)) {
            $seoData->image = VoodbuilderSettings::assetUrl('seo_default_image');
        }

        if (blank($seoData->site_name) && filled($settings['seo_site_name'] ?? null)) {
            $seoData->site_name = (string) $settings['seo_site_name'];
        } elseif (blank($seoData->site_name)) {
            $seoData->site_name = VoodbuilderSettings::siteTitle();
        }

        if (blank($seoData->favicon)) {
            $seoData->favicon = VoodbuilderSettings::faviconUrl();
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

        $seoData->schema = self::mergeOrganizationSchema($seoData);

        return $seoData;
    }

    /**
     * Emit light/dark favicon variants when configured.
     *
     * Browsers select via `prefers-color-scheme` (OS/browser chrome), not the site theme toggle.
     */
    public static function transformTags(TagCollection $tags): TagCollection
    {
        $light = VoodbuilderSettings::faviconLightUrl();
        $dark = VoodbuilderSettings::faviconDarkUrl();
        $fallback = VoodbuilderSettings::faviconUrl();

        if ($light === null && $dark === null) {
            return $tags;
        }

        $filtered = $tags->reject(fn (mixed $tag): bool => $tag instanceof FaviconTag)->values();

        // Order matters: Chrome picks the *last matching* icon. Put the unscoped
        // fallback first, then scheme-specific links so they win when they match.
        // Safari tends to pick the first; the fallback covers that case.
        $filtered->push(new MediaFaviconLinkTag($light ?? $dark ?? $fallback));

        if ($dark !== null) {
            $filtered->push(new MediaFaviconLinkTag($dark, '(prefers-color-scheme: dark)'));
        }

        if ($light !== null) {
            $filtered->push(new MediaFaviconLinkTag($light, '(prefers-color-scheme: light)'));
        }

        return new TagCollection($filtered->all());
    }

    /**
     * Replace empty titles and homepage URL-inferred host titles (e.g. "Localhost:8014").
     */
    public static function shouldReplaceTitle(?string $title): bool
    {
        if (blank($title)) {
            return true;
        }

        if (! config('seo.title.infer_title_from_url', true)) {
            return false;
        }

        $path = trim((string) (parse_url(url()->current(), PHP_URL_PATH) ?: ''), '/');

        if ($path !== '') {
            return false;
        }

        $inferred = Str::of(url()->current())
            ->afterLast('/')
            ->headline()
            ->toString();

        return $inferred !== '' && $title === $inferred;
    }

    protected static function mergeOrganizationSchema(SEOData $seoData): ?SchemaCollection
    {
        $settings = VoodbuilderSettings::data();
        $organizationName = $settings['geo_organization_name'] ?? null;
        $organizationLogo = VoodbuilderSettings::assetUrl('geo_organization_logo');
        $siteSummary = $settings['geo_site_summary'] ?? $settings['seo_default_description'] ?? null;

        if (blank($organizationName) && blank($organizationLogo) && blank($siteSummary)) {
            return $seoData->schema;
        }

        $schema = $seoData->schema ?? SchemaCollection::initialize();

        $organization = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $organizationName ?: VoodbuilderSettings::siteTitle(),
            'url' => url('/'),
            'logo' => $organizationLogo,
            'description' => $siteSummary,
        ]);

        $website = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $settings['seo_site_name'] ?? VoodbuilderSettings::siteTitle(),
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
        $settings = VoodbuilderSettings::data();

        return array_filter([
            'geo.region' => $settings['geo_region'] ?? null,
            'geo.placename' => $settings['geo_placename'] ?? null,
            'abstract' => $settings['geo_site_summary'] ?? null,
            'ai:description' => $settings['geo_site_summary'] ?? null,
        ]);
    }
}
