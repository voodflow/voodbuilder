<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\PageTemplate;

class StarterPageTemplates
{
    /**
     * @var array<string, string>|null
     */
    private static ?array $blocks = null;

    /**
     * @return list<string>
     */
    private static function staleNames(): array
    {
        return [
            'Marketing landing',
            'SaaS homepage',
            'Contact page',
            'About us',
            'Blank starter',
            'News homepage',
            'Blog magazine',
            'Ecommerce store',
            'Photo gallery',
            'Business landing',
        ];
    }

    /**
     * @return list<array{name: string, category: string, description: string, html: string, css: string|null, js: string|null}>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Auto parts megastore',
                'category' => 'Ecommerce',
                'description' => 'Auto-parts storefront with promo strip, hero, product grid and feature band.',
                'html' => self::page(
                    self::siteNav(),
                    self::promoStrip('Free shipping on orders over €79 · Use code GARAGE20'),
                    self::injectPexelsImages(self::customizeHero(
                        self::block('vb-hero-1'),
                        'Performance parts for every ride',
                        'OEM-quality brakes, filters and tools with same-day dispatch from our EU warehouse.',
                    ), [3806286, 4489702]),
                    self::injectPexelsImages(self::block('vb-ecommerce-1'), [
                        3806286, 4489702, 5632402, 6476589, 1571460, 3184465, 3861969, 768125,
                    ]),
                    self::injectPexelsImages(self::withSectionClasses(self::block('vb-content-3'), 'bg-vp-bg-alt'), [
                        265087, 768125, 1571460, 1181244, 3401403, 6476589,
                    ]),
                    self::withSectionClasses(self::block('vb-cta-1'), 'bg-primary text-primary-foreground'),
                    self::siteFooter(),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Culture blog',
                'category' => 'Blogging',
                'description' => 'Editorial blog home with featured stories, author grid and newsletter band.',
                'html' => self::page(
                    self::siteNav(),
                    self::injectPexelsImages(self::customizeHero(
                        self::block('vb-hero-1'),
                        'Stories, guides and ideas for modern publishers',
                        'A magazine-style homepage with room for categories, trending posts and newsletter signup.',
                    ), [1181244, 3401403]),
                    self::injectPexelsImages(self::block('vb-blog-2'), [3861969, 768125, 1571460, 3184465, 265087, 6476589]),
                    self::promoStrip('New issue out now · Subscribe for weekly reads'),
                    self::injectPexelsImages(self::block('vb-blog-1'), [3184465, 768125, 3861969, 1181244, 1571460, 6476589]),
                    self::siteFooter(),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Growth campaign',
                'category' => 'Marketing',
                'description' => 'Marketing page with feature grid, pricing and signup CTA.',
                'html' => self::page(
                    self::siteNav(),
                    self::injectPexelsImages(self::customizeHero(
                        self::block('vb-hero-1'),
                        'Turn traffic into qualified pipeline',
                        'Launch campaigns with landing pages, forms and analytics in one visual workspace.',
                    ), [3861969, 3184465]),
                    self::injectPexelsImages(self::withSectionClasses(self::block('vb-content-3'), 'bg-vp-bg-alt'), [
                        265087, 768125, 1571460, 1181244, 3401403, 6476589,
                    ]),
                    self::withSectionClasses(self::block('vb-pricing-1'), 'bg-vp-bg-alt'),
                    self::block('vb-cta-3'),
                    self::siteFooter(),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Startup landing',
                'category' => 'Landing pages',
                'description' => 'Focused SaaS landing with social proof, benefits and conversion CTA.',
                'html' => self::page(
                    self::siteNav(),
                    self::injectPexelsImages(self::customizeHero(
                        self::block('vb-hero-1'),
                        'Ship product updates without the busywork',
                        'Replace scattered docs and decks with one live page your whole team can edit.',
                    ), [3861969, 3184465]),
                    self::promoStrip('Trusted by 2,400+ teams in 38 countries'),
                    self::injectPexelsImages(self::block('vb-content-2'), [265087, 768125, 1571460, 1181244]),
                    self::withSectionClasses(self::block('vb-cta-2'), 'bg-vp-brand-3 text-vp-text-1'),
                    self::siteFooter(),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Design portfolio',
                'category' => 'Creative',
                'description' => 'Visual portfolio with gallery grid, showcase band and contact CTA.',
                'html' => self::page(
                    self::siteNav(),
                    self::injectPexelsImages(self::customizeHero(
                        self::block('vb-hero-1'),
                        'Crafted digital experiences for ambitious brands',
                        'Selected projects across product, identity and campaign design.',
                    ), [1181244, 3401403]),
                    self::injectPexelsImages(self::block('vb-gallery-3'), [
                        1181244, 3401403, 768125, 265087, 3861969, 1571460, 6476589, 3184465, 5632402,
                    ]),
                    self::withSectionClasses(self::block('vb-cta-4'), 'bg-primary text-primary-foreground'),
                    self::siteFooter(),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Company profile',
                'category' => 'Corporate',
                'description' => 'Corporate homepage with mission band, team highlights and contact section.',
                'html' => self::page(
                    self::siteNav(),
                    self::injectPexelsImages(self::customizeHero(
                        self::block('vb-hero-1'),
                        'Building reliable infrastructure for growing companies',
                        'We help enterprises modernize operations with secure, scalable digital products.',
                    ), [3401403, 3861969]),
                    self::withSectionClasses(self::block('vb-content-1'), 'bg-vp-bg-alt'),
                    self::injectPexelsImages(self::block('vb-team-1'), [1571460, 768125, 265087, 1181244]),
                    self::block('vb-contact-1'),
                    self::siteFooter(),
                ),
                'css' => null,
                'js' => null,
            ],
        ];
    }

    public static function seed(): int
    {
        if (! Schema::hasTable('voodbuilder_page_templates')) {
            return 0;
        }

        PageTemplate::query()->where('category', 'General')->update(['category' => 'Miscellaneous']);
        PageTemplate::query()->whereIn('name', self::staleNames())->delete();

        $created = 0;

        foreach (self::definitions() as $definition) {
            $template = PageTemplate::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'category' => $definition['category'],
                    'description' => $definition['description'],
                    'html' => $definition['html'],
                    'css' => $definition['css'],
                    'js' => $definition['js'],
                ],
            );

            if ($template->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    private static function page(string ...$parts): string
    {
        return implode('', $parts);
    }

    private static function siteNav(): string
    {
        return '<div class="voodbuilder-gjs-section" data-voodbuilder-block="site_nav_simple"></div>';
    }

    private static function siteFooter(): string
    {
        return '<div class="voodbuilder-gjs-section" data-voodbuilder-block="site_footer_columns_simple"></div>';
    }

    private static function promoStrip(string $text, string $background = 'bg-primary'): string
    {
        return '<section class="'.$background.' text-primary-foreground"><div class="voodbuilder-gjs-container px-5 py-3"><p class="text-center text-sm font-semibold uppercase tracking-widest">'
            .e($text)
            .'</p></div></section>';
    }

    private static function withSectionClasses(string $html, string $classes): string
    {
        if (preg_match('/<section class="([^"]*)"/', $html) !== 1) {
            return $html;
        }

        return preg_replace(
            '/<section class="([^"]*)"/',
            '<section class="'.$classes.' $1"',
            $html,
            1,
        ) ?? $html;
    }

    /**
     * @param  list<string>  $links
     */
    private static function brandedHeader(string $siteName, array $links): string
    {
        $header = self::block('vb-header-1');
        $header = str_replace('VoodBuilder', $siteName, $header);
        $defaults = ['First Link', 'Second Link', 'Third Link', 'Fourth Link'];

        foreach ($defaults as $index => $default) {
            $header = str_replace($default, $links[$index] ?? $default, $header);
        }

        return str_replace('>Submit<', '>Subscribe<', $header);
    }

    private static function brandedFooter(string $siteName): string
    {
        return str_replace('VoodBuilder', $siteName, self::block('vb-footer-1'));
    }

    private static function customizeHero(string $hero, string $title, string $subtitle): string
    {
        $hero = preg_replace(
            '/<h1 class="[^"]*">.*?<\/h1>/s',
            '<h1 class="font-semibold sm:text-4xl text-3xl mb-4 font-medium text-vp-text-1">'.e($title).'</h1>',
            $hero,
            1,
        ) ?? $hero;

        return preg_replace(
            '/<p class="mb-8 leading-relaxed">.*?<\/p>/s',
            '<p class="mb-8 leading-relaxed text-vp-text-2">'.e($subtitle).'</p>',
            $hero,
            1,
        ) ?? $hero;
    }

    private static function block(string $id): string
    {
        $block = self::blocksCatalog()[$id] ?? '';

        if ($block === '') {
            throw new \RuntimeException("Missing section block [{$id}] for starter page templates.");
        }

        return $block;
    }

    /**
     * @return array<string, string>
     */
    private static function blocksCatalog(): array
    {
        if (self::$blocks !== null) {
            return self::$blocks;
        }

        $path = dirname(__DIR__, 3).'/resources/grapesjs/section-blocks.json';
        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            self::$blocks = [];

            return self::$blocks;
        }

        $catalog = [];

        foreach ($payload as $entry) {
            if (! is_array($entry) || ! isset($entry['id'], $entry['content'])) {
                continue;
            }

            $catalog[(string) $entry['id']] = (string) $entry['content'];
        }

        self::$blocks = $catalog;

        return self::$blocks;
    }

    /**
     * @param  list<int>  $photoIds
     */
    private static function injectPexelsImages(string $html, array $photoIds): string
    {
        if ($photoIds === []) {
            return $html;
        }

        $index = 0;

        return (string) preg_replace_callback(
            '/src="data:image\/svg\+xml[^"]*"/',
            static function () use ($photoIds, &$index): string {
                $photoId = $photoIds[$index] ?? $photoIds[array_key_last($photoIds)];
                $index++;

                return 'src="'.self::pexelsUrl($photoId).'"';
            },
            $html,
        );
    }

    private static function pexelsUrl(int $photoId, int $width = 1200, int $height = 800): string
    {
        return sprintf(
            'https://images.pexels.com/photos/%1$d/pexels-photo-%1$d.jpeg?auto=compress&cs=tinysrgb&w=%2$d&h=%3$d&dpr=1',
            $photoId,
            $width,
            $height,
        );
    }
}
