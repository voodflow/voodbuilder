<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * HTML payloads for the local Voodflow marketing site (slug prefix "a").
 */
final class MarketingSiteContent
{
    /**
     * @return array<string, array{title: string, html: string, layout?: string, sub_theme?: string, is_home?: bool, css?: string}>
     */
    public static function pages(): array
    {
        $plugins = self::pluginCatalog();

        $pages = [
            'a' => [
                'title' => 'Voodflow — Laravel automation & content plugins',
                'layout' => 'landing',
                'is_home' => false,
                'html' => self::hero(
                    'Build faster with Voodflow',
                    'A modular plugin suite for Laravel and Filament: visual pages, workflows, AI, events, forms, media, and more.',
                    '/pages/a-plugins',
                    'Explore plugins',
                ).self::pluginGrid($plugins),
            ],
            'a-plugins' => [
                'title' => 'All plugins',
                'html' => self::sectionHeading('Plugin catalog').self::pluginGrid($plugins),
            ],
        ];

        foreach ($plugins as $plugin) {
            $pages['a-'.$plugin['slug']] = [
                'title' => $plugin['name'],
                'html' => self::pluginDetail($plugin),
            ];
        }

        $pages['a-docs'] = [
            'title' => 'Documentation',
            'html' => self::sectionHeading('Documentation').<<<'HTML'
<p class="mb-6 max-w-2xl text-sm leading-relaxed text-gray-600">Product docs are served by <strong>vdocs</strong> at <a href="/docs" class="text-vp-brand-1 underline">/docs</a>. Use Filament to author sections and pages; the public site inherits your theme.</p>
<ul class="grid gap-3 sm:grid-cols-2">
  <li class="rounded-xl bg-gray-50 p-4 ring-1 ring-black/5"><a href="/docs" class="font-semibold text-gray-900">Browse docs</a><p class="mt-1 text-xs text-gray-500">vdocs channel</p></li>
  <li class="rounded-xl bg-gray-50 p-4 ring-1 ring-black/5"><a href="/pages/a-voodflow" class="font-semibold text-gray-900">Voodflow core</a><p class="mt-1 text-xs text-gray-500">Workflows &amp; automation</p></li>
  <li class="rounded-xl bg-gray-50 p-4 ring-1 ring-black/5"><a href="/pages/a-voodbuilder" class="font-semibold text-gray-900">VoodBuilder</a><p class="mt-1 text-xs text-gray-500">Visual site builder</p></li>
  <li class="rounded-xl bg-gray-50 p-4 ring-1 ring-black/5"><a href="/pages/a-voodflow-ai" class="font-semibold text-gray-900">Voodflow AI</a><p class="mt-1 text-xs text-gray-500">Agents in workflows</p></li>
</ul>
HTML,
        ];

        $pages['a-tutorials'] = [
            'title' => 'Tutorials',
            'html' => self::sectionHeading('Tutorials').<<<'HTML'
<p class="mb-6 max-w-2xl text-sm leading-relaxed text-gray-600">Hands-on guides powered by <strong>vtuts</strong> at <a href="/tutorials" class="text-vp-brand-1 underline">/tutorials</a>. Create series, categories, and step-by-step lessons in Filament.</p>
<a href="/tutorials" class="inline-flex rounded-lg bg-vp-brand-1 px-6 py-3 text-sm font-semibold text-white hover:bg-vp-brand-2">Open tutorials</a>
HTML,
        ];

        return $pages;
    }

    /**
     * @return list<array{slug: string, name: string, tagline: string, description: string, features: list<string>}>
     */
    public static function pluginCatalog(): array
    {
        return [
            ['slug' => 'voodflow', 'name' => 'Voodflow', 'tagline' => 'Workflow automation', 'description' => 'Visual flow editor, triggers, executions, credentials, and model integrations for Laravel apps.', 'features' => ['Flow canvas', 'Schedule & webhooks', 'OEM multi-tenant']],
            ['slug' => 'voodflow-ai', 'name' => 'Voodflow AI', 'tagline' => 'AI in workflows', 'description' => 'Agent integrations for prompt and class-based AI nodes inside automations.', 'features' => ['Workflow agents', 'Usage tracking', 'Credential routing']],
            ['slug' => 'voodflow-ai-weaver', 'name' => 'Flow Weaver', 'tagline' => 'Canvas AI assistant', 'description' => 'Chat inside the flow editor to design and refine automations with blueprint apply.', 'features' => ['Sidebar chat', 'Weaver agents', 'Test workflow']],
            ['slug' => 'voodbuilder', 'name' => 'VoodBuilder', 'tagline' => 'Visual pages', 'description' => 'Editor-powered marketing pages, navigation, themes, and companion blocks.', 'features' => ['Editor blocks', 'Sub-themes', 'Dynamic routes']],
            ['slug' => 'vdocs', 'name' => 'vdocs', 'tagline' => 'Documentation', 'description' => 'Fixed docs channel with sections, search, and Filament authoring.', 'features' => ['/docs routes', 'Sections & pages', 'Theme integration']],
            ['slug' => 'vtuts', 'name' => 'vtuts', 'tagline' => 'Tutorials', 'description' => 'Tutorial series and lessons with categories, tags, and public frontend.', 'features' => ['/tutorials channel', 'Series & steps', 'Rich content']],
            ['slug' => 'vmedia', 'name' => 'vmedia', 'tagline' => 'Media library', 'description' => 'Galleries, tags, and Filament media picker for pages and forms.', 'features' => ['Galleries', 'Responsive images', 'Filament integration']],
            ['slug' => 'vforms', 'name' => 'vforms', 'tagline' => 'Forms', 'description' => 'Form builder, submissions, data sources, and workflow hooks.', 'features' => ['Visual forms', 'Submissions', 'Voodflow events']],
            ['slug' => 'vevents', 'name' => 'vevents', 'tagline' => 'Events', 'description' => 'Event listings, schedules, organizers, and exhibitor landing pages.', 'features' => ['Schedules', 'Locations', 'Public listings']],
            ['slug' => 'vexhibitors', 'name' => 'vexhibitors', 'tagline' => 'Exhibitors', 'description' => 'Exhibitor profiles, sectors, and bindings to event pages.', 'features' => ['Profiles', 'Sectors', 'Dynamic blocks']],
            ['slug' => 'vsponsors', 'name' => 'vsponsors', 'tagline' => 'Sponsors', 'description' => 'Sponsor tiers, logos, and placement on event sites.', 'features' => ['Tiers', 'Logo grids', 'Filament CRUD']],
            ['slug' => 'vpartners', 'name' => 'vpartners', 'tagline' => 'Partners', 'description' => 'Partner directories with sectors and detail pages.', 'features' => ['Partner CRUD', 'Sectors', 'Public views']],
            ['slug' => 'vgen', 'name' => 'vgen', 'tagline' => 'Generative content', 'description' => 'AI-assisted jokes, characters, assets, books, and comic strips.', 'features' => ['Projects', 'Generation pipeline', 'Voodflow demo flows']],
            ['slug' => 'voodflow-oem', 'name' => 'Voodflow OEM', 'tagline' => 'Multi-tenant SaaS', 'description' => 'Tenant quotas, usage counters, insights dashboard, and expiry automation.', 'features' => ['Tenant insights', 'Quota enforcement', 'OEM nodes']],
        ];
    }

    /**
     * @param  list<array{slug: string, name: string, tagline: string, description: string, features: list<string>}>  $plugins
     */
    protected static function pluginGrid(array $plugins): string
    {
        $cards = '';

        foreach ($plugins as $plugin) {
            $cards .= <<<HTML
<a href="/pages/a-{$plugin['slug']}" class="group block rounded-2xl bg-white p-6 ring-1 ring-black/5 transition hover:ring-vp-brand-1/30">
  <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">{$plugin['tagline']}</p>
  <h3 class="mt-2 text-lg font-bold text-gray-900 group-hover:text-vp-brand-1">{$plugin['name']}</h3>
  <p class="mt-2 text-sm leading-relaxed text-gray-600">{$plugin['description']}</p>
</a>
HTML;
        }

        return <<<HTML
<section class="bg-gray-50 py-16 body-font">
  <div class="container mx-auto grid gap-6 px-5 sm:grid-cols-2 lg:grid-cols-3">{$cards}</div>
</section>
HTML;
    }

    protected static function hero(string $title, string $subtitle, string $ctaHref, string $ctaLabel): string
    {
        return <<<HTML
<section class="relative flex min-h-[60vh] items-center bg-gray-900 text-white body-font">
  <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=1920&q=80" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30" />
  <div class="absolute inset-0 bg-gray-900/75"></div>
  <div class="relative z-10 container mx-auto px-5 py-20">
    <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-vp-brand-2">Voodflow ecosystem</p>
    <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">{$title}</h1>
    <p class="mt-4 max-w-2xl text-lg text-gray-200">{$subtitle}</p>
    <div class="mt-8 flex flex-wrap gap-4">
      <a href="{$ctaHref}" class="rounded-lg bg-vp-brand-1 px-8 py-3 text-sm font-semibold text-white hover:bg-vp-brand-2">{$ctaLabel}</a>
      <a href="/pages/a-docs" class="rounded-lg border border-white/30 px-8 py-3 text-sm font-semibold hover:bg-white/10">Documentation</a>
      <a href="/pages/a-tutorials" class="rounded-lg border border-white/30 px-8 py-3 text-sm font-semibold hover:bg-white/10">Tutorials</a>
    </div>
  </div>
</section>
HTML;
    }

    /**
     * @param  array{slug: string, name: string, tagline: string, description: string, features: list<string>}  $plugin
     */
    protected static function pluginDetail(array $plugin): string
    {
        $features = implode('', array_map(
            fn (string $f): string => "<li class=\"text-sm text-gray-600\">{$f}</li>",
            $plugin['features'],
        ));

        return <<<HTML
<section class="bg-white py-16 body-font">
  <div class="container mx-auto max-w-3xl px-5">
    <a href="/pages/a-plugins" class="text-sm font-medium text-vp-brand-1 hover:underline">← All plugins</a>
    <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">{$plugin['tagline']}</p>
    <h1 class="mt-2 text-4xl font-bold text-gray-900">{$plugin['name']}</h1>
    <p class="mt-4 text-lg leading-relaxed text-gray-600">{$plugin['description']}</p>
    <ul class="mt-8 grid gap-2 sm:grid-cols-2">{$features}</ul>
    <div class="mt-10 flex gap-4">
      <a href="/docs" class="rounded-lg bg-vp-brand-1 px-6 py-3 text-sm font-semibold text-white">Read docs</a>
      <a href="/pages/a" class="rounded-lg ring-1 ring-black/10 px-6 py-3 text-sm font-semibold text-gray-700">Back to home</a>
    </div>
  </div>
</section>
HTML;
    }

    protected static function sectionHeading(string $title): string
    {
        return <<<HTML
<section class="bg-white py-12 body-font">
  <div class="container mx-auto px-5">
    <h1 class="text-3xl font-bold text-gray-900">{$title}</h1>
  </div>
</section>
HTML;
    }
}
