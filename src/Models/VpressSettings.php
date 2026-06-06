<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VpressSettings extends Model
{
    protected $table = 'vpress_settings';

    protected $fillable = [
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'site_title' => config('vpress.site_title', config('app.name')),
            'brand_name' => null,
            'show_site_title' => true,
            'show_notification_bell' => true,
            'show_theme_toggle' => true,
            'theme_mode' => 'system',
            'show_account_link' => true,
            'sticky_nav' => false,
            'show_language_switcher' => true,
            'primary_locale' => null,
            'default_ui_locale' => null,
            'logo' => null,
            'logo_mobile' => null,
            'favicon' => null,
            'seo_default_description' => null,
            'seo_default_image' => null,
            'seo_site_name' => null,
            'seo_title_suffix' => null,
            'seo_default_author' => null,
            'seo_twitter_username' => null,
            'seo_robots' => 'max-snippet:-1,max-image-preview:large,max-video-preview:-1',
            'seo_canonical_enabled' => true,
            'geo_organization_name' => null,
            'geo_organization_logo' => null,
            'geo_region' => null,
            'geo_placename' => null,
            'geo_site_summary' => null,
            'facebook_pixel_id' => null,
            'google_tag_manager_id' => null,
            'google_analytics_id' => null,
            'monitoring_head_code' => null,
            'monitoring_body_code' => null,
        ];
    }

    public static function data(): array
    {
        return Cache::rememberForever('vpress.settings', function (): array {
            $record = static::query()->first();

            if ($record === null) {
                return static::defaults();
            }

            return static::normalizeData(array_merge(static::defaults(), $record->data ?? []));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function normalizeData(array $data): array
    {
        if (blank($data['primary_locale'] ?? null) && filled($data['default_ui_locale'] ?? null)) {
            $data['primary_locale'] = $data['default_ui_locale'];
        }

        return $data;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(static::data(), $key, $default);
    }

    public static function siteTitle(): string
    {
        $title = static::get('site_title');

        if (filled($title)) {
            return (string) $title;
        }

        return (string) config('vpress.site_title', config('app.name'));
    }

    public static function brandName(): string
    {
        $brand = static::get('brand_name');

        if (filled($brand)) {
            return (string) $brand;
        }

        return static::siteTitle();
    }

    public static function primaryLocale(): string
    {
        $locale = static::get('primary_locale') ?? static::get('default_ui_locale');

        if (is_string($locale) && $locale !== '') {
            if (class_exists(\Voodflow\Tutorials\Support\Locales::class)) {
                if (\Voodflow\Tutorials\Support\Locales::isValid($locale)) {
                    return $locale;
                }
            } else {
                return $locale;
            }
        }

        $configured = (string) config('tutorials.default_locale', config('app.locale', 'en'));

        if (class_exists(\Voodflow\Tutorials\Support\Locales::class)) {
            return \Voodflow\Tutorials\Support\Locales::isValid($configured)
                ? $configured
                : \Voodflow\Tutorials\Support\Locales::codes()[0];
        }

        return $configured;
    }

    public static function defaultUiLocale(): string
    {
        return static::primaryLocale();
    }

    public static function logoUrl(): ?string
    {
        return static::assetUrl('logo', config('vpress.logo'));
    }

    public static function logoMobileUrl(): ?string
    {
        return static::assetUrl('logo_mobile');
    }

    public static function assetUrl(string $key, mixed $fallback = null): ?string
    {
        $value = static::get($key, $fallback);

        return static::resolvePublicAsset($value);
    }

    public static function saveData(array $data): void
    {
        if (! ($data['show_theme_toggle'] ?? true) && ($data['theme_mode'] ?? 'system') === 'system') {
            $data['theme_mode'] = 'light';
        }

        if (array_key_exists('primary_locale', $data) && class_exists(\Voodflow\Tutorials\Support\Locales::class)) {
            $locale = $data['primary_locale'];

            if (! is_string($locale) || ! \Voodflow\Tutorials\Support\Locales::isValid($locale)) {
                $data['primary_locale'] = \Voodflow\Tutorials\Support\Locales::codes()[0];
            }

            $data['default_ui_locale'] = $data['primary_locale'];
        }

        foreach (['logo', 'logo_mobile', 'favicon', 'seo_default_image', 'geo_organization_logo'] as $uploadKey) {
            if (array_key_exists($uploadKey, $data)) {
                $data[$uploadKey] = static::normalizeUploadValue($data[$uploadKey]);
            }
        }

        $record = static::query()->firstOrNew(['id' => 1]);
        $record->data = array_merge(static::data(), $data);
        $record->save();

        Cache::forget('vpress.settings');
    }

    public static function normalizeLogoValue(mixed $logo): ?string
    {
        return static::normalizeUploadValue($logo);
    }

    public static function normalizeUploadValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (blank($value)) {
            return null;
        }

        return (string) $value;
    }

    public static function resolvePublicAsset(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (blank($value)) {
            return null;
        }

        $value = (string) $value;

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/'.$value);
    }

    public static function clearCache(): void
    {
        Cache::forget('vpress.settings');
    }
}
