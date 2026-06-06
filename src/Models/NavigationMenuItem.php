<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;
use Voodflow\Vpress\Enums\MenuItemType;

class NavigationMenuItem extends Model
{
    protected $table = 'vpress_menu_items';

    protected $fillable = [
        'menu_id',
        'label',
        'type',
        'link',
        'route_match',
        'open_in_new_tab',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    public function resolveUrl(): string
    {
        return match ($this->type) {
            MenuItemType::Page => $this->resolvePageUrl(),
            MenuItemType::Route => Route::has($this->link) ? route($this->link) : '#',
            MenuItemType::Url => $this->link,
        };
    }

    public function isExternal(): bool
    {
        return $this->type === MenuItemType::Url || $this->open_in_new_tab;
    }

    public function isActive(): bool
    {
        if ($this->type === MenuItemType::Page) {
            return $this->isActivePageLink();
        }

        if (blank($this->route_match)) {
            return false;
        }

        return request()->routeIs($this->route_match);
    }

    protected function isActivePageLink(): bool
    {
        if (blank($this->link)) {
            return false;
        }

        $page = SitePage::query()->where('slug', $this->link)->first();

        if ($page?->is_home) {
            return request()->routeIs('home');
        }

        return request()->routeIs('vpress.pages.show')
            && request()->route('slug') === $this->link;
    }

    protected function resolvePageUrl(): string
    {
        $page = SitePage::query()
            ->published()
            ->where('slug', $this->link)
            ->first();

        return $page?->getUrl() ?? '#';
    }
}
