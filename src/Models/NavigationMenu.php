<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Voodflow\Voodbuilder\Support\Navigation;

class NavigationMenu extends Model
{
    protected $table = 'voodbuilder_menus';

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::updating(function (NavigationMenu $menu): void {
            if ($menu->isDirty('slug')) {
                $previousSlug = $menu->getOriginal('slug');

                if (is_string($previousSlug) && $previousSlug !== '') {
                    Navigation::clearCache($previousSlug);
                }
            }
        });

        static::saved(function (NavigationMenu $menu): void {
            Navigation::clearCache($menu->slug);
        });

        static::deleted(function (NavigationMenu $menu): void {
            Navigation::clearCache($menu->slug);
        });
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
}
