<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Casts\MenuItemTypeCast;
use Voodflow\Voodbuilder\Contracts\MenuItemTypeHandler;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Support\NavigationMenuItemTree;
use Voodflow\Voodbuilder\Support\SitePageResolver;

/**
 * Navigation Menu Item.
 */
class NavigationMenuItem extends Model
{
    protected $table = 'voodbuilder_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'icon',
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
            'type' => MenuItemTypeCast::class,
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
                'parent_id' => __('voodbuilder::admin.validation.menu_max_depth', [
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
        if ($this->navigationChildren()->isNotEmpty()) {
            return true;
        }

        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        return $this->children()->exists();
    }

    /**
     * Children used for front-end rendering (persisted + registered type children).
     *
     * @return Collection<int, NavigationMenuItem>
     */
    public function navigationChildren(): Collection
    {
        $handler = $this->registeredTypeHandler();

        if ($handler !== null) {
            $dynamic = $handler->resolveChildren($this);

            if ($dynamic->isNotEmpty()) {
                return $dynamic;
            }
        }

        if ($this->relationLoaded('children')) {
            return $this->children;
        }

        if (! $this->exists) {
            return collect();
        }

        return $this->children()->orderBy('sort_order')->get();
    }

    public function typeKey(): string
    {
        return $this->type instanceof MenuItemType
            ? $this->type->value
            : (string) $this->type;
    }

    public function registeredTypeHandler(): ?MenuItemTypeHandler
    {
        if ($this->type instanceof MenuItemType) {
            return null;
        }

        if (! function_exists('app') || ! app()->bound(MenuItemTypeRegistry::class)) {
            return null;
        }

        return app(MenuItemTypeRegistry::class)->get($this->typeKey());
    }

    public function resolveUrl(): string
    {
        if ($this->type === MenuItemType::Group) {
            return '#';
        }

        $handler = $this->registeredTypeHandler();

        if ($handler !== null) {
            return $handler->resolveUrl($this);
        }

        if (! $this->type instanceof MenuItemType) {
            return '#';
        }

        return match ($this->type) {
            MenuItemType::Page => $this->resolvePageUrl(),
            MenuItemType::Route => $this->resolveRouteUrl(),
            MenuItemType::Url => (string) ($this->link ?? '#'),
            MenuItemType::Mail => $this->resolveMailUrl(),
            MenuItemType::Group => '#',
        };
    }

    public function hasResolvableLink(): bool
    {
        if ($this->type === MenuItemType::Group) {
            return false;
        }

        $handler = $this->registeredTypeHandler();

        if ($handler !== null) {
            return $handler->hasResolvableLink($this);
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
        return in_array($this->type, [MenuItemType::Url, MenuItemType::Mail], true) || $this->open_in_new_tab;
    }

    public function isActive(): bool
    {
        if ($this->isSelfActive()) {
            return true;
        }

        return $this->navigationChildren()->contains(fn (NavigationMenuItem $child): bool => $child->isActive());
    }

    public function isSelfActive(): bool
    {
        if ($this->type === MenuItemType::Group) {
            return false;
        }

        $handler = $this->registeredTypeHandler();

        if ($handler !== null) {
            return $handler->isActive($this);
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
            return request()->routeIs('home', 'home.localized');
        }

        if ($page?->isSectionHome() && filled($page->section)) {
            $resolved = SitePageResolver::publishedForMenu((string) request()->route('slug'));

            return request()->routeIs('voodbuilder.pages.show')
                && $resolved !== null
                && $resolved->section === $page->section
                && $resolved->section_home;
        }

        return request()->routeIs('voodbuilder.pages.show')
            && SitePageResolver::publishedForMenu((string) $this->link)?->getKey()
                === SitePageResolver::publishedForMenu((string) request()->route('slug'))?->getKey();
    }

    protected function resolvePageUrl(): string
    {
        $page = SitePageResolver::publishedForMenu((string) $this->link);

        return $page?->getUrl() ?? '#';
    }

    protected function resolveMailUrl(): string
    {
        $email = (string) ($this->link ?? '');

        if ($email === '') {
            return '#';
        }

        return str_starts_with($email, 'mailto:') ? $email : 'mailto:'.$email;
    }
}
