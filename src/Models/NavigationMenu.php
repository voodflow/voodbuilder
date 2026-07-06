<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Enums\MenuLinkDisplay;
use Voodflow\Voodbuilder\Support\Navigation;
use Voodflow\Voodbuilder\Support\NavigationMenuResolver;
use Voodflow\Vtuts\Support\Locales;

class NavigationMenu extends Model
{
    protected $table = 'voodbuilder_menus';

    protected $fillable = [
        'name',
        'slug',
        'locale',
        'translation_group_id',
        'link_display',
    ];

    protected function casts(): array
    {
        return [
            'link_display' => MenuLinkDisplay::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (NavigationMenu $menu): void {
            if (blank($menu->locale)) {
                $menu->locale = class_exists(Locales::class) ? Locales::default() : 'en';
            }

            if (blank($menu->translation_group_id)) {
                $menu->translation_group_id = (string) Str::uuid();
            }
        });

        static::updating(function (NavigationMenu $menu): void {
            if ($menu->isDirty('slug')) {
                $previousSlug = $menu->getOriginal('slug');

                if (is_string($previousSlug) && $previousSlug !== '') {
                    Navigation::clearCache($previousSlug, $menu->getOriginal('locale'));
                }
            }
        });

        static::saved(function (NavigationMenu $menu): void {
            Navigation::clearCache($menu->slug, $menu->locale);
        });

        static::deleted(function (NavigationMenu $menu): void {
            Navigation::clearCache($menu->slug, $menu->locale);
        });
    }

    /** @return HasMany<NavigationMenu, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(self::class, 'translation_group_id', 'translation_group_id')
            ->whereKeyNot($this->getKey());
    }

    public function translationFor(string $locale): ?self
    {
        if ($this->locale === $locale) {
            return $this;
        }

        if (blank($this->translation_group_id)) {
            return null;
        }

        return static::query()
            ->where('translation_group_id', $this->translation_group_id)
            ->where('locale', $locale)
            ->first();
    }

    /** @return list<string> */
    public function translationLocaleCodes(): array
    {
        if (blank($this->translation_group_id)) {
            return [strtoupper((string) $this->locale)];
        }

        return static::query()
            ->where('translation_group_id', $this->translation_group_id)
            ->orderBy('locale')
            ->pluck('locale')
            ->map(fn (string $locale): string => strtoupper($locale))
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function otherTranslationLocaleCodes(): array
    {
        if (blank($this->translation_group_id)) {
            return [];
        }

        return static::query()
            ->where('translation_group_id', $this->translation_group_id)
            ->where('locale', '!=', $this->locale)
            ->orderBy('locale')
            ->pluck('locale')
            ->map(fn (string $locale): string => strtoupper($locale))
            ->values()
            ->all();
    }

    public function items(): HasMany
    {
        return $this->hasMany(NavigationMenuItem::class, 'menu_id')
            ->orderBy('sort_order');
    }

    /** @return HasMany<NavigationMenuItem, $this> */
    public function rootItems(): HasMany
    {
        return $this->hasMany(NavigationMenuItem::class, 'menu_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public static function forPlacement(string $slug, ?string $locale = null): ?self
    {
        return NavigationMenuResolver::forPlacement($slug, $locale);
    }
}
