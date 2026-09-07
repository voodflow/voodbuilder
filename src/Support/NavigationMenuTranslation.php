<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Voodflow\Vevents\Models\Event;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Vtuts\Support\Locales;

/**
 * Navigation Menu Translation.
 */
final class NavigationMenuTranslation
{
    /**
     * @return array<string, string> locale => label
     */
    public static function availableTargetLocales(NavigationMenu $menu): array
    {
        $existing = self::existingLocales($menu);

        return collect(Locales::options())
            ->reject(fn (string $label, string $code): bool => $code === $menu->locale || in_array($code, $existing, true))
            ->all();
    }

    /** @return list<string> */
    public static function existingLocales(NavigationMenu $menu): array
    {
        self::ensureTranslationGroup($menu);

        return NavigationMenu::query()
            ->where('translation_group_id', $menu->translation_group_id)
            ->whereKeyNot($menu->getKey())
            ->pluck('locale')
            ->all();
    }

    public static function createFrom(NavigationMenu $source, string $targetLocale): NavigationMenu
    {
        if (! in_array($targetLocale, Locales::codes(), true)) {
            throw new \InvalidArgumentException("Unsupported locale [{$targetLocale}].");
        }

        if ($targetLocale === $source->locale) {
            throw new \InvalidArgumentException('Target locale must differ from the source menu.');
        }

        self::ensureTranslationGroup($source);

        if (NavigationMenu::query()
            ->where('translation_group_id', $source->translation_group_id)
            ->where('locale', $targetLocale)
            ->exists()) {
            throw new \InvalidArgumentException("A translation for [{$targetLocale}] already exists.");
        }

        $source->loadMissing(['rootItems.children']);

        $translation = self::replicateMenu($source);
        $translation->fill([
            'locale' => $targetLocale,
            'translation_group_id' => $source->translation_group_id,
        ]);
        $translation->save();

        self::duplicateItems($source, $translation, $targetLocale);

        return $translation->refresh();
    }

    public static function cloneAsDuplicate(
        NavigationMenu $source,
        string $newSlug,
        ?string $newName = null,
    ): NavigationMenu {
        if (NavigationMenu::query()
            ->where('slug', $newSlug)
            ->where('locale', $source->locale)
            ->exists()) {
            throw new \InvalidArgumentException("A menu with placement [{$newSlug}] already exists for [{$source->locale}].");
        }

        $source->loadMissing(['rootItems.children']);

        $clone = self::replicateMenu($source);
        $clone->fill([
            'slug' => $newSlug,
            'name' => $newName ?? $source->name.' (copy)',
            'translation_group_id' => (string) Str::uuid(),
        ]);
        $clone->save();

        self::duplicateItems($source, $clone, $source->locale);

        return $clone->refresh();
    }

    public static function ensureTranslationGroup(NavigationMenu $menu): void
    {
        if (filled($menu->translation_group_id)) {
            return;
        }

        $menu->forceFill([
            'translation_group_id' => (string) Str::uuid(),
        ])->saveQuietly();
    }

    public static function duplicateItems(
        NavigationMenu $source,
        NavigationMenu $target,
        ?string $targetLocale = null,
    ): void {
        foreach ($source->rootItems as $rootItem) {
            self::cloneItemTree($rootItem, $target, null, $targetLocale);
        }
    }

    protected static function replicateMenu(NavigationMenu $source): NavigationMenu
    {
        $except = array_diff(
            array_keys($source->getAttributes()),
            $source->getFillable(),
        );

        return $source->replicate($except);
    }

    protected static function cloneItemTree(
        NavigationMenuItem $item,
        NavigationMenu $targetMenu,
        ?int $parentId,
        ?string $targetLocale,
    ): NavigationMenuItem {
        $clone = $item->replicate(['menu_id', 'parent_id']);
        $clone->menu_id = $targetMenu->id;
        $clone->parent_id = $parentId;

        if ($targetLocale !== null && $item->type === MenuItemType::Page && filled($item->link)) {
            $page = SitePage::query()->where('slug', $item->link)->first();
            $translated = $page?->translationFor($targetLocale);

            if ($translated !== null) {
                $clone->link = $translated->slug;
                $clone->route_match = $translated->is_home ? 'home' : $item->route_match;
            }
        }

        if ($targetLocale !== null && $item->type === MenuItemType::Route && filled($item->link)) {
            self::localizeRouteMenuItem($clone, $targetLocale);
        }

        $clone->save();

        foreach ($item->children as $child) {
            self::cloneItemTree($child, $targetMenu, $clone->id, $targetLocale);
        }

        return $clone;
    }

    protected static function localizeRouteMenuItem(NavigationMenuItem $item, string $targetLocale): void
    {
        $link = (string) $item->link;

        if (preg_match('/^vevents\.([^.]+)\.(.+)$/', $link, $matches) === 1) {
            $item->link = 'vevents.'.$targetLocale.'.'.$matches[2];

            if (is_string($item->route_match) && preg_match('/^vevents\.([^.]+)\./', $item->route_match) === 1) {
                $item->route_match = preg_replace(
                    '/^vevents\.[^.]+\./',
                    'vevents.'.$targetLocale.'.',
                    $item->route_match,
                );
            }
        }

        $parameters = is_array($item->route_parameters) ? $item->route_parameters : [];

        if (isset($parameters['slug']) && is_string($parameters['slug']) && class_exists(Event::class)) {
            try {
                $event = Event::resolvePublishedSlug($parameters['slug']);
                $localized = $event->translationFor($targetLocale);

                if ($localized !== null) {
                    $parameters['slug'] = (string) $localized->slug;
                }
            } catch (ModelNotFoundException) {
                // Keep the original slug when the event cannot be resolved.
            }
        }

        if (isset($parameters['eventSlug']) && is_string($parameters['eventSlug']) && class_exists(Event::class)) {
            try {
                $event = Event::resolvePublishedSlug($parameters['eventSlug']);
                $localized = $event->translationFor($targetLocale);

                if ($localized !== null) {
                    $parameters['eventSlug'] = (string) $localized->slug;
                }
            } catch (ModelNotFoundException) {
                // Keep the original slug when the event cannot be resolved.
            }
        }

        $item->route_parameters = $parameters === [] ? null : $parameters;
    }
}
