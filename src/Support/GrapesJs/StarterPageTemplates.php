<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\PageTemplate;

/**
 * Starter page templates = composition of existing section blocks only.
 * Keep stacks small (2–3 sections) so the editor stays responsive.
 * Nav/footer are omitted — chrome-shell page editors already provide them.
 */
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
                'description' => 'Storefront starter: promo, hero, product grid and CTA.',
                'html' => self::page(
                    self::promoStrip('Free shipping on orders over €79 · Use code GARAGE20'),
                    self::customizeHero(
                        self::block('vb-hero-1'),
                        'Performance parts for every ride',
                        'OEM-quality brakes, filters and tools with same-day dispatch from our EU warehouse.',
                    ),
                    self::block('vb-ecommerce-1'),
                    self::withSectionClasses(self::block('vb-cta-1'), 'bg-primary text-primary-foreground'),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Culture blog',
                'category' => 'Blogging',
                'description' => 'Editorial home: hero, post grid and newsletter strip.',
                'html' => self::page(
                    self::customizeHero(
                        self::block('vb-hero-1'),
                        'Stories, guides and ideas for modern publishers',
                        'A magazine-style homepage with room for categories, trending posts and newsletter signup.',
                    ),
                    self::block('vb-blog-1'),
                    self::promoStrip('New issue out now · Subscribe for weekly reads'),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Growth campaign',
                'category' => 'Marketing',
                'description' => 'Campaign landing: hero, benefits and signup CTA.',
                'html' => self::page(
                    self::customizeHero(
                        self::block('vb-hero-1'),
                        'Turn traffic into qualified pipeline',
                        'Launch campaigns with landing pages, forms and analytics in one visual workspace.',
                    ),
                    self::withSectionClasses(self::block('vb-content-1'), 'bg-vp-bg-alt'),
                    self::block('vb-cta-3'),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Startup landing',
                'category' => 'Landing pages',
                'description' => 'SaaS landing: hero, benefits and conversion CTA.',
                'html' => self::page(
                    self::customizeHero(
                        self::block('vb-hero-1'),
                        'Ship product updates without the busywork',
                        'Replace scattered docs and decks with one live page your whole team can edit.',
                    ),
                    self::promoStrip('Trusted by 2,400+ teams in 38 countries'),
                    self::block('vb-content-2'),
                    self::withSectionClasses(self::block('vb-cta-2'), 'bg-vp-brand-3 text-vp-text-1'),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Design portfolio',
                'category' => 'Creative',
                'description' => 'Portfolio: hero, gallery and contact CTA.',
                'html' => self::page(
                    self::customizeHero(
                        self::block('vb-hero-1'),
                        'Crafted digital experiences for ambitious brands',
                        'Selected projects across product, identity and campaign design.',
                    ),
                    self::block('vb-gallery-3'),
                    self::withSectionClasses(self::block('vb-cta-4'), 'bg-primary text-primary-foreground'),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => 'Company profile',
                'category' => 'Corporate',
                'description' => 'Corporate home: hero, mission band and contact.',
                'html' => self::page(
                    self::customizeHero(
                        self::block('vb-hero-1'),
                        'Building reliable infrastructure for growing companies',
                        'We help enterprises modernize operations with secure, scalable digital products.',
                    ),
                    self::withSectionClasses(self::block('vb-content-1'), 'bg-vp-bg-alt'),
                    self::block('vb-contact-1'),
                ),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => VoodbuilderLanding01Sections::TEMPLATE_NAME,
                'category' => 'Landing pages',
                'description' => 'Astrolus-style VoodBuilder landing: hero, features, solution, testimonials, articles and CTA.',
                'html' => VoodbuilderLanding01Sections::pageHtml(),
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
}
