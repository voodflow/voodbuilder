<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\PageTemplate;

/**
 * Curated starter page templates (Landing 01–03 only).
 * Nav/footer are omitted — chrome-shell page editors already provide them.
 */
class StarterPageTemplates
{
    /**
     * @return list<string>
     */
    public static function keepNames(): array
    {
        return [
            VoodbuilderLanding01Sections::TEMPLATE_NAME,
            VoodbuilderLanding02Sections::TEMPLATE_NAME,
            VoodbuilderLanding03Sections::TEMPLATE_NAME,
        ];
    }

    /**
     * Legacy / demo template names removed on seed.
     *
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
            'Auto parts megastore',
            'Culture blog',
            'Growth campaign',
            'Startup landing',
            'Design portfolio',
            'Company profile',
            'VoodBuilder landing 01',
            'VoodBuilder landing 02',
            'VoodBuilder NASA',
        ];
    }

    /**
     * @return list<array{name: string, category: string, description: string, html: string, css: string|null, js: string|null}>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => VoodbuilderLanding01Sections::TEMPLATE_NAME,
                'category' => 'Landing pages',
                'description' => 'Gradient SaaS landing: hero, features, solution, testimonials, articles and CTA.',
                'html' => EditorSmartButtonAnnotator::annotate(VoodbuilderLanding01Sections::pageHtml()),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => VoodbuilderLanding02Sections::TEMPLATE_NAME,
                'category' => 'Landing pages',
                'description' => 'Editorial landing: hero, trust, features, impact, toolkit, articles and FAQ.',
                'html' => EditorSmartButtonAnnotator::annotate(VoodbuilderLanding02Sections::pageHtml()),
                'css' => null,
                'js' => null,
            ],
            [
                'name' => VoodbuilderLanding03Sections::TEMPLATE_NAME,
                'category' => 'Landing pages',
                'description' => 'Cinematic home: hero, stats, featured stories, teaser, spotlight, missions and explore.',
                'html' => EditorSmartButtonAnnotator::annotate(VoodbuilderLanding03Sections::pageHtml()),
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
        PageTemplate::query()->whereNotIn('name', self::keepNames())->delete();

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
}
