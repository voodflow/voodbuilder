<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsComponentCategoryNormalizer
{
    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        $configured = config('voodbuilder.grapesjs.component_categories');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_map(static fn (mixed $value): string => (string) $value, $configured));
        }

        return self::defaultCategories();
    }

    public static function normalize(?string $category, ?string $fallback = null): ?string
    {
        $categories = self::categories();
        $fallback ??= $categories[0] ?? 'General';
        $raw = trim((string) $category);

        if ($raw === '') {
            return $fallback;
        }

        foreach ($categories as $canonical) {
            if (strcasecmp($raw, $canonical) === 0) {
                return $canonical;
            }
        }

        foreach (self::aliases() as $alias => $canonical) {
            if (strcasecmp($raw, $alias) !== 0) {
                continue;
            }

            if (in_array($canonical, $categories, true)) {
                return $canonical;
            }
        }

        return $fallback;
    }

    /**
     * @return list<string>
     */
    private static function defaultCategories(): array
    {
        return [
            'General',
            'Hero',
            'Content',
            'Features',
            'Articles',
            'Gallery',
            'Stats',
            'Testimonials',
            'Team',
            'Steps',
            'Pricing',
            'CTA',
            'Contact',
            'Shop',
            'Header',
            'Footer',
            'Code',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function aliases(): array
    {
        return [
            'sections' => 'Content',
            'section' => 'Content',
            'uncategorized' => 'General',
            'general' => 'General',
            'heroes' => 'Hero',
            'feature' => 'Features',
            'features' => 'Features',
            'article' => 'Articles',
            'articles' => 'Articles',
            'galleries' => 'Gallery',
            'gallery' => 'Gallery',
            'stat' => 'Stats',
            'stats' => 'Stats',
            'testimonial' => 'Testimonials',
            'testimonials' => 'Testimonials',
            'teams' => 'Team',
            'team' => 'Team',
            'step' => 'Steps',
            'steps' => 'Steps',
            'price' => 'Pricing',
            'pricing' => 'Pricing',
            'call to action' => 'CTA',
            'contacts' => 'Contact',
            'contact' => 'Contact',
            'shops' => 'Shop',
            'shop' => 'Shop',
            'headers' => 'Header',
            'header' => 'Header',
            'footers' => 'Footer',
            'footer' => 'Footer',
            'codes' => 'Code',
            'code' => 'Code',
        ];
    }
}
