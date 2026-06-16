<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Voodflow\Vpress\Enums\MenuItemType;
use Voodflow\Vpress\Support\NavigationMenuItemTree;

class NavigationMenuItem extends Model
{
    protected $table = 'vpress_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'type',
        'link',
        'route_parameters',
        'route_match',
        'open_in_new_tab',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
            'route_parameters' => 'array',
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (NavigationMenuItem $item): void {
            if (filled($item->parent_id) && blank($item->menu_id)) {
                $item->menu_id = static::query()
                    ->whereKey($item->parent_id)
                    ->value('menu_id');
            }
        });

        static::saving(function (NavigationMenuItem $item): void {
            static::assertValidNestingDepth($item);
        });
    }

    public static function assertValidNestingDepth(NavigationMenuItem $item): void
    {
        if (! filled($item->parent_id)) {
            return;
        }

        $parent = $item->relationLoaded('parent')
            ? $item->parent
            : static::query()->find($item->parent_id);

        if ($parent?->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => __('vpress::admin.validation.menu_max_depth', [
                    'max' => NavigationMenuItemTree::MAX_DEPTH,
                ]),
            ]);
        }
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<NavigationMenuItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order');
    }

    public function hasChildren(): bool
    {
        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        return $this->children()->exists();
    }

    public function resolveUrl(): string
    {
        if ($this->type === MenuItemType::Group) {
            return '#';
        }

        return match ($this->type) {
            MenuItemType::Page => $this->resolvePageUrl(),
            MenuItemType::Route => $this->resolveRouteUrl(),
            MenuItemType::Url => (string) ($this->link ?? '#'),
        };
    }

    public function hasResolvableLink(): bool
    {
        if ($this->type === MenuItemType::Group) {
            return false;
        }

        return $this->resolveUrl() !== '#';
    }

    protected function resolveRouteUrl(): string
    {
        if (blank($this->link) || ! Route::has($this->link)) {
            return '#';
        }

        try {
            return route($this->link, $this->routeParameters());
        } catch (\Throwable) {
            return '#';
        }
    }

    /** @return array<string, mixed> */
    protected function routeParameters(): array
    {
        return array_filter(
            $this->route_parameters ?? [],
            fn (mixed $value): bool => filled($value),
        );
    }

    public function isExternal(): bool
    {
        return $this->type === MenuItemType::Url || $this->open_in_new_tab;
    }

    public function isActive(): bool
    {
        if ($this->isSelfActive()) {
            return true;
        }

        return $this->loadedChildren()->contains(fn (NavigationMenuItem $child): bool => $child->isActive());
    }

    public function isSelfActive(): bool
    {
        if ($this->type === MenuItemType::Group) {
            return false;
        }

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

        if ($page?->isSectionHome() && filled($page->section)) {
            return request()->routeIs('vpress.pages.show')
                && SitePage::query()
                    ->where('slug', request()->route('slug'))
                    ->where('section', $page->section)
                    ->exists();
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

    /** @return Collection<int, NavigationMenuItem> */
    protected function loadedChildren(): Collection
    {
        return $this->relationLoaded('children') ? $this->children : collect();
    }
}
