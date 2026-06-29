<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    protected $table = 'voodbuilder_menus';

    protected $fillable = [
        'name',
        'slug',
    ];

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
