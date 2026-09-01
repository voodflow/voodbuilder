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
        return [
            'a' => [
                'title' => 'Voodflow — Laravel automation & Filament plugins',
                'layout' => 'landing',
                'sub_theme' => 'site',
                'is_home' => false,
                'html' => self::homePage(),
            ],
            'a-voodflow' => [
                'title' => 'Voodflow — Workflow automation for Laravel',
                'layout' => 'landing',
                'sub_theme' => 'site',
                'html' => self::voodflowPage(),
            ],
            'a-voodbuilder' => [
                'title' => 'VoodBuilder — Visual site builder for Laravel',
                'layout' => 'landing',
                'sub_theme' => 'site',
                'html' => self::voodbuilderPage(),
            ],
            'a-events-suite' => [
                'title' => 'Events & Exhibitors — Trade fair management suite',
                'layout' => 'landing',
                'sub_theme' => 'site',
                'html' => self::eventsSuitePage(),
            ],
            'a-vdocs' => [
                'title' => 'vdocs — Documentation plugin for Filament',
                'layout' => 'landing',
                'sub_theme' => 'site',
                'html' => self::vdocsPage(),
            ],
            'a-vtuts' => [
                'title' => 'vtuts — Tutorials plugin for Filament',
                'layout' => 'landing',
                'sub_theme' => 'site',
                'html' => self::vtutsPage(),
            ],
        ];
    }

    /**
     * Product landings shown in nav, home grid, and footer.
     *
     * @return list<array{slug: string, name: string, tagline: string, description: string, href: string}>
     */
    public static function productLandings(): array
    {
        return [
            [
                'slug' => 'voodflow',
                'name' => 'Voodflow',
                'tagline' => 'Workflow automation',
                'description' => 'Visual flows, triggers, credentials, and model integrations — the automation core of the suite.',
                'href' => '/pages/a-voodflow',
            ],
            [
                'slug' => 'voodbuilder',
                'name' => 'VoodBuilder',
                'tagline' => 'Visual site builder',
                'description' => 'Marketing pages, chrome layouts, themes, and Editor blocks — ship landings without leaving Laravel.',
                'href' => '/pages/a-voodbuilder',
            ],
            [
                'slug' => 'events-suite',
                'name' => 'Events & Exhibitors',
                'tagline' => 'Trade fair package',
                'description' => 'vevents, vexhibitors, vpartners, and vsponsors — one package for event and exhibition sites.',
                'href' => '/pages/a-events-suite',
            ],
            [
                'slug' => 'vdocs',
                'name' => 'vdocs',
                'tagline' => 'Documentation',
                'description' => 'Fixed docs channel with sections, search, and Filament authoring for product documentation.',
                'href' => '/pages/a-vdocs',
            ],
            [
                'slug' => 'vtuts',
                'name' => 'vtuts',
                'tagline' => 'Tutorials',
                'description' => 'Series, categories, and step-by-step lessons with a public tutorials channel.',
                'href' => '/pages/a-vtuts',
            ],
        ];
    }

    protected static function homePage(): string
    {
        return self::hero(
            eyebrow: 'Voodflow ecosystem',
            title: 'Build faster with a modular Filament plugin suite',
            subtitle: 'Voodflow brings workflow automation, visual pages, documentation, tutorials, and event management into one Laravel-native stack — designed for teams already on Filament.',
            primaryHref: '/pages/a-voodflow',
            primaryLabel: 'Explore Voodflow',
            secondaryHref: '#products',
            secondaryLabel: 'View products',
            image: 'https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=1920&q=80',
        )
            .self::statsBar([
                ['value' => '5+', 'label' => 'Core products'],
                ['value' => 'Filament 5', 'label' => 'Admin-native'],
                ['value' => 'Laravel', 'label' => 'First-class'],
                ['value' => 'Modular', 'label' => 'Pay for what you need'],
            ])
            .self::sectionOpen('products', 'The Voodflow suite', 'Pick the plugins you need — each ships as a focused Filament companion with its own public channel where it makes sense.')
            .self::productGrid(self::productLandings())
            .self::sectionClose()
            .self::valuePropSection()
            .self::premiumTeaser()
            .self::commercialTeaser()
            .self::ctaSection(
                title: 'Ready to ship on Laravel?',
                subtitle: 'Start with Voodflow automation and VoodBuilder landings, then add docs, tutorials, or the events package as your product grows.',
                href: '/pages/a-voodflow',
                label: 'Get started with Voodflow',
            );
    }

    protected static function voodflowPage(): string
    {
        return self::hero(
            eyebrow: 'Voodflow',
            title: 'Workflow automation that lives inside your Laravel app',
            subtitle: 'Design flows in Filament, trigger them on schedules, webhooks, and model events, and integrate credentials, queues, and third-party APIs — without a separate automation SaaS.',
            primaryHref: '#features',
            primaryLabel: 'See capabilities',
            secondaryHref: '/pages/a',
            secondaryLabel: 'Back to home',
            image: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1920&q=80',
        )
            .self::featureGrid('Core capabilities', [
                ['title' => 'Visual flow canvas', 'body' => 'Drag-and-drop nodes for triggers, conditions, HTTP calls, model actions, and custom integrations.'],
                ['title' => 'Executions & logging', 'body' => 'Trace every run, inspect payloads, and debug failures from Filament — no black-box jobs.'],
                ['title' => 'Credentials vault', 'body' => 'Store API keys and OAuth tokens securely, then reference them from any node.'],
                ['title' => 'Filament-native', 'body' => 'Resources, policies, and permissions follow the same patterns as the rest of your admin.'],
                ['title' => 'Event hooks', 'body' => 'React to Eloquent events, form submissions, and companion package signals.'],
                ['title' => 'Queue-ready', 'body' => 'Long-running steps dispatch to Laravel queues with retries and failure handling.'],
            ])
            .self::premiumSection()
            .self::commercialSection()
            .self::ctaSection(
                title: 'Automate your next release workflow',
                subtitle: 'Pair Voodflow with VoodBuilder landings and vdocs product pages for a complete go-to-market stack.',
                href: '/pages/a',
                label: 'Explore the suite',
            );
    }

    protected static function voodbuilderPage(): string
    {
        return self::hero(
            eyebrow: 'VoodBuilder',
            title: 'Marketing sites and landings — built in Laravel',
            subtitle: 'A visual page builder with chrome layouts, sub-themes, navigation menus, and Editor blocks. Ship product landings like this one without a headless CMS.',
            primaryHref: '#features',
            primaryLabel: 'See what you get',
            secondaryHref: '/pages/a-voodflow',
            secondaryLabel: 'Pair with Voodflow',
            image: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1920&q=80',
        )
            .self::splitSection(
                title: 'From blank canvas to published landing',
                image: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=1400&q=80',
                items: [
                    'Compose pages with Editor blocks, Style Manager, and theme tokens.',
                    'Chrome layouts own header and footer — page content stays focused.',
                    'Sub-themes switch accents per channel: marketing, docs, events, and more.',
                    'Menus, SEO, and locales are managed from Filament.',
                ],
            )
            .self::featureGrid('Why teams pick VoodBuilder', [
                ['title' => 'Editor in the browser', 'body' => 'Open any published page with ?edit=1 and iterate visually while the live site keeps its chrome.'],
                ['title' => 'Template marketplace', 'body' => 'Save, reuse, and install page templates from secure bundle URLs.'],
                ['title' => 'Companion blocks', 'body' => 'Dynamic blocks for events, exhibitors, forms, and media galleries plug into the same canvas.'],
                ['title' => 'No Google Fonts CDN', 'body' => 'Self-hosted fonts and JIT CSS keep performance and privacy under your control.'],
            ])
            .self::ctaSection(
                title: 'Build your next landing in Filament',
                subtitle: 'Use VoodBuilder for marketing pages and pair with vdocs or vtuts for product education.',
                href: '/pages/a-vdocs',
                label: 'See vdocs',
            );
    }

    protected static function eventsSuitePage(): string
    {
        return self::hero(
            eyebrow: 'Events & Exhibitors suite',
            title: 'Everything you need for trade fairs and exhibitions',
            subtitle: 'A coordinated package — vevents, vexhibitors, vpartners, and vsponsors — for public event sites, exhibitor directories, partner listings, and sponsor tiers.',
            primaryHref: '#package',
            primaryLabel: 'What\'s included',
            secondaryHref: '/pages/a-voodbuilder',
            secondaryLabel: 'Built on VoodBuilder',
            image: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1920&q=80',
        )
            .self::sectionOpen('package', 'One package, four plugins', 'Each plugin focuses on a slice of event operations while sharing themes, blocks, and Filament resources.')
            .self::featureGrid('', [
                ['title' => 'vevents', 'body' => 'Schedules, locations, organizers, and public event listings with dynamic routes.'],
                ['title' => 'vexhibitors', 'body' => 'Exhibitor profiles, sectors, and landing pages bound to event contexts.'],
                ['title' => 'vpartners', 'body' => 'Partner directories with sectors, logos, and detail pages for co-marketing.'],
                ['title' => 'vsponsors', 'body' => 'Sponsor tiers, logo grids, and placement blocks across event pages.'],
            ], dark: true)
            .self::sectionClose()
            .self::splitSection(
                title: 'Designed for exhibition marketing sites',
                image: 'https://images.unsplash.com/photo-1505373877841-8d25f39c3f0e?auto=format&fit=crop&w=1400&q=80',
                items: [
                    'Showcase sub-theme with dark header and card-forward layouts.',
                    'Dynamic blocks drop exhibitor and sponsor data into VoodBuilder pages.',
                    'Filament CRUD for editors who already manage the rest of the stack.',
                    'Locale-ready public routes for international fairs.',
                ],
            )
            .self::ctaSection(
                title: 'Launch your next fair site on Laravel',
                subtitle: 'Combine the events suite with VoodBuilder landings and Voodflow automations for registrations and notifications.',
                href: '/pages/a-voodflow',
                label: 'Explore Voodflow',
            );
    }

    protected static function vdocsPage(): string
    {
        return self::hero(
            eyebrow: 'vdocs',
            title: 'Product documentation — authored in Filament, served on your domain',
            subtitle: 'A fixed /docs channel with sections, sidebar navigation, search, and theme integration. No separate docs platform required.',
            primaryHref: '/docs',
            primaryLabel: 'Browse demo docs',
            secondaryHref: '#features',
            secondaryLabel: 'Features',
            image: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1920&q=80',
        )
            .self::featureGrid('Built for product teams', [
                ['title' => 'Sections & pages', 'body' => 'Organize reference, guides, and API docs with nested navigation.'],
                ['title' => 'Search', 'body' => 'Built-in search across published pages with fast, server-rendered results.'],
                ['title' => 'Theme integration', 'body' => 'Inherits chrome layouts and sub-themes from VoodBuilder for a seamless brand.'],
                ['title' => 'Rich content', 'body' => 'Filament RichEditor blocks for callouts, code samples, and embedded media.'],
            ])
            .self::ctaSection(
                title: 'Docs and marketing on one stack',
                subtitle: 'Pair vdocs with VoodBuilder landings and vtuts for onboarding content.',
                href: '/pages/a-vtuts',
                label: 'See vtuts',
            );
    }

    protected static function vtutsPage(): string
    {
        return self::hero(
            eyebrow: 'vtuts',
            title: 'Tutorials your users will actually finish',
            subtitle: 'Series, categories, tags, and step-by-step lessons on a dedicated /tutorials channel — authored in Filament, styled like the rest of your site.',
            primaryHref: '/tutorials',
            primaryLabel: 'Browse demo tutorials',
            secondaryHref: '#features',
            secondaryLabel: 'Features',
            image: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1920&q=80',
        )
            .self::featureGrid('From onboarding to advanced guides', [
                ['title' => 'Series & steps', 'body' => 'Group lessons into series with progress-friendly step pages.'],
                ['title' => 'Categories & tags', 'body' => 'Help visitors discover content by topic or skill level.'],
                ['title' => 'Shared chrome', 'body' => 'Documentation-style layout pairs naturally with vdocs on the same site.'],
                ['title' => 'Filament authoring', 'body' => 'Editors use familiar admin tools — no Markdown repo required.'],
            ])
            .self::ctaSection(
                title: 'Educate users without leaving Laravel',
                subtitle: 'Combine vtuts with vdocs reference material and Voodflow automations for certification flows.',
                href: '/pages/a-vdocs',
                label: 'See vdocs',
            );
    }

    /**
     * @param  list<array{slug: string, name: string, tagline: string, description: string, href: string}>  $products
     */
    protected static function productGrid(array $products): string
    {
        $cards = '';

        foreach ($products as $product) {
            $cards .= <<<HTML
<a href="{$product['href']}" class="group block rounded-2xl bg-vp-bg-elv p-6 ring-1 ring-black/5 transition hover:ring-vp-brand-1/40">
  <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">{$product['tagline']}</p>
  <h3 class="mt-2 text-lg font-bold text-vp-text-1 group-hover:text-vp-brand-1">{$product['name']}</h3>
  <p class="mt-2 text-sm leading-relaxed text-vp-text-2">{$product['description']}</p>
  <span class="mt-4 inline-flex text-sm font-semibold text-vp-brand-1">Learn more →</span>
</a>
HTML;
        }

        return <<<HTML
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">{$cards}</div>
HTML;
    }

    protected static function hero(
        string $eyebrow,
        string $title,
        string $subtitle,
        string $primaryHref,
        string $primaryLabel,
        string $secondaryHref,
        string $secondaryLabel,
        string $image,
    ): string {
        return <<<HTML
<section class="relative flex min-h-[72vh] items-center bg-gray-900 text-white body-font">
  <img src="{$image}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-35" />
  <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-900/90 to-vp-brand-3/30"></div>
  <div class="relative z-10 mx-auto w-full max-w-6xl px-6 py-24">
    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.25em] text-vp-brand-2">{$eyebrow}</p>
    <h1 class="max-w-4xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">{$title}</h1>
    <p class="mt-6 max-w-2xl text-lg leading-relaxed text-gray-200">{$subtitle}</p>
    <div class="mt-10 flex flex-wrap gap-4">
      <a href="{$primaryHref}" class="inline-flex rounded-lg bg-vp-brand-1 px-8 py-3 text-sm font-semibold text-white hover:bg-vp-brand-2">{$primaryLabel}</a>
      <a href="{$secondaryHref}" class="inline-flex rounded-lg border border-white/25 px-8 py-3 text-sm font-semibold text-white hover:bg-white/10">{$secondaryLabel}</a>
    </div>
  </div>
</section>
HTML;
    }

    /**
     * @param  list<array{value: string, label: string}>  $stats
     */
    protected static function statsBar(array $stats): string
    {
        $items = '';

        foreach ($stats as $stat) {
            $items .= <<<HTML
<div>
  <p class="text-3xl font-bold text-vp-brand-1">{$stat['value']}</p>
  <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-vp-text-3">{$stat['label']}</p>
</div>
HTML;
        }

        return <<<HTML
<section class="border-y border-vp-divider bg-vp-bg-alt py-14 body-font">
  <div class="mx-auto grid max-w-6xl gap-8 px-6 text-center sm:grid-cols-2 md:grid-cols-4">{$items}</div>
</section>
HTML;
    }

    protected static function sectionOpen(string $id, string $title, string $subtitle): string
    {
        return <<<HTML
<section id="{$id}" class="bg-vp-bg py-20 body-font">
  <div class="mx-auto max-w-6xl px-6">
    <div class="mx-auto mb-14 max-w-2xl text-center">
      <h2 class="text-3xl font-bold text-vp-text-1">{$title}</h2>
      <p class="mt-4 text-base leading-relaxed text-vp-text-2">{$subtitle}</p>
    </div>
HTML;
    }

    protected static function sectionClose(): string
    {
        return <<<'HTML'
  </div>
</section>
HTML;
    }

    protected static function valuePropSection(): string
    {
        return <<<'HTML'
<section class="bg-vp-bg-alt py-20 body-font">
  <div class="mx-auto max-w-6xl px-6">
    <div class="grid items-center gap-12 lg:grid-cols-2">
      <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Why Voodflow</p>
        <h2 class="mb-6 text-3xl font-bold text-vp-text-1">Laravel-native, Filament-first, modular by design</h2>
        <p class="mb-4 leading-relaxed text-vp-text-2">Stop bolting together separate SaaS tools for docs, landings, automation, and event microsites. Voodflow plugins share themes, permissions, and deployment with your existing app.</p>
        <p class="leading-relaxed text-vp-text-2">Install only what you need — add documentation, tutorials, or the events package when your product roadmap calls for it.</p>
      </div>
      <div class="rounded-3xl bg-vp-bg-elv p-8 ring-1 ring-black/5">
        <ul class="space-y-4 text-sm leading-relaxed text-vp-text-2">
          <li class="flex gap-3"><span class="text-vp-brand-1">✓</span><span><strong class="text-vp-text-1">One admin</strong> — editors stay in Filament.</span></li>
          <li class="flex gap-3"><span class="text-vp-brand-1">✓</span><span><strong class="text-vp-text-1">One deploy</strong> — no separate frontend to host.</span></li>
          <li class="flex gap-3"><span class="text-vp-brand-1">✓</span><span><strong class="text-vp-text-1">Composable</strong> — workflows can react to form submissions, docs updates, and more.</span></li>
          <li class="flex gap-3"><span class="text-vp-brand-1">✓</span><span><strong class="text-vp-text-1">Brand control</strong> — themes, chrome layouts, and tokens stay on your domain.</span></li>
        </ul>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    protected static function premiumTeaser(): string
    {
        return <<<'HTML'
<section class="bg-gray-900 py-20 text-white body-font">
  <div class="mx-auto max-w-6xl px-6">
    <div class="mx-auto max-w-3xl text-center">
      <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-vp-brand-2">Premium</p>
      <h2 class="text-3xl font-bold">AI superpowers inside Voodflow</h2>
      <p class="mt-4 text-base leading-relaxed text-gray-300">Voodflow AI and Flow Weaver are premium add-ons for the automation core — agent nodes in flows, usage tracking, and an in-canvas assistant to design automations faster.</p>
    </div>
    <div class="mt-12 grid gap-6 md:grid-cols-2">
      <article class="rounded-2xl bg-white/5 p-8 ring-1 ring-white/10">
        <h3 class="text-lg font-semibold text-white">Voodflow AI</h3>
        <p class="mt-2 text-sm leading-relaxed text-gray-300">Prompt and class-based AI nodes inside your workflows, with credential routing and usage tracking.</p>
      </article>
      <article class="rounded-2xl bg-white/5 p-8 ring-1 ring-white/10">
        <h3 class="text-lg font-semibold text-white">Flow Weaver</h3>
        <p class="mt-2 text-sm leading-relaxed text-gray-300">Chat inside the flow editor to draft, refine, and apply automation blueprints without leaving Filament.</p>
      </article>
    </div>
    <p class="mt-8 text-center text-sm text-gray-400">Available as premium extensions to Voodflow — not standalone plugins.</p>
  </div>
</section>
HTML;
    }

    protected static function commercialTeaser(): string
    {
        return <<<'HTML'
<section class="border-y border-vp-divider bg-vp-bg py-16 body-font">
  <div class="mx-auto max-w-6xl px-6">
    <div class="grid gap-8 md:grid-cols-2">
      <article class="rounded-2xl bg-vp-bg-elv p-8 ring-1 ring-black/5">
        <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Commercial option</p>
        <h3 class="mt-2 text-xl font-bold text-vp-text-1">White-label licensing</h3>
        <p class="mt-3 text-sm leading-relaxed text-vp-text-2">Rebrand the suite for agencies and product studios shipping under their own identity. Sold separately — talk to us for packaging.</p>
      </article>
      <article class="rounded-2xl bg-vp-bg-elv p-8 ring-1 ring-black/5">
        <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Commercial option</p>
        <h3 class="mt-2 text-xl font-bold text-vp-text-1">OEM / multi-tenant</h3>
        <p class="mt-3 text-sm leading-relaxed text-vp-text-2">Tenant quotas, usage counters, and expiry automation for SaaS vendors embedding Voodflow. Licensed separately from core plugins.</p>
      </article>
    </div>
  </div>
</section>
HTML;
    }

    protected static function premiumSection(): string
    {
        return <<<'HTML'
<section id="premium" class="bg-gray-900 py-20 text-white body-font">
  <div class="mx-auto max-w-6xl px-6">
    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-vp-brand-2">Premium extensions</p>
    <h2 class="text-3xl font-bold">AI built into your automations</h2>
    <p class="mt-4 max-w-2xl text-base leading-relaxed text-gray-300">Voodflow AI and Flow Weaver extend the core — they are not separate products. Add agent nodes, track usage, and design flows conversationally when you need more than deterministic steps.</p>
    <div class="mt-10 grid gap-6 md:grid-cols-2">
      <article class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
        <h3 class="font-semibold text-white">Voodflow AI nodes</h3>
        <p class="mt-2 text-sm text-gray-300">Drop AI steps into any flow with credential-aware routing and observability.</p>
      </article>
      <article class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
        <h3 class="font-semibold text-white">Flow Weaver assistant</h3>
        <p class="mt-2 text-sm text-gray-300">Sidebar chat in the canvas to propose blueprints you can review and apply.</p>
      </article>
    </div>
  </div>
</section>
HTML;
    }

    protected static function commercialSection(): string
    {
        return <<<'HTML'
<section class="bg-vp-bg-alt py-20 body-font">
  <div class="mx-auto max-w-6xl px-6">
    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Commercial options</p>
    <h2 class="text-3xl font-bold text-vp-text-1">Scale with licensing that fits your business</h2>
    <div class="mt-10 grid gap-6 md:grid-cols-2">
      <article class="rounded-2xl bg-vp-bg-elv p-8 ring-1 ring-black/5">
        <h3 class="text-lg font-semibold text-vp-text-1">White-label licensing</h3>
        <p class="mt-2 text-sm leading-relaxed text-vp-text-2">Ship the suite under your brand for clients and reseller programs. Priced and contracted separately from individual plugins.</p>
      </article>
      <article class="rounded-2xl bg-vp-bg-elv p-8 ring-1 ring-black/5">
        <h3 class="text-lg font-semibold text-vp-text-1">OEM / multi-tenant</h3>
        <p class="mt-2 text-sm leading-relaxed text-vp-text-2">Embed Voodflow in your SaaS with tenant insights, quota enforcement, and expiry automation. OEM is a commercial license — not a feature toggle.</p>
      </article>
    </div>
  </div>
</section>
HTML;
    }

    /**
     * @param  list<array{title: string, body: string}>  $features
     */
    protected static function featureGrid(string $title, array $features, bool $dark = false): string
    {
        $heading = $title !== ''
            ? '<h2 class="mb-10 text-center text-3xl font-bold '.($dark ? 'text-white' : 'text-vp-text-1').'">'.$title.'</h2>'
            : '';

        $cards = '';

        foreach ($features as $feature) {
            $cardBg = $dark ? 'bg-white/5 ring-white/10' : 'bg-vp-bg-elv ring-black/5';
            $titleClass = $dark ? 'text-white' : 'text-vp-text-1';
            $bodyClass = $dark ? 'text-gray-300' : 'text-vp-text-2';

            $cards .= <<<HTML
<article class="rounded-2xl {$cardBg} p-8 ring-1">
  <h3 class="text-lg font-semibold {$titleClass}">{$feature['title']}</h3>
  <p class="mt-3 text-sm leading-relaxed {$bodyClass}">{$feature['body']}</p>
</article>
HTML;
        }

        $sectionBg = $dark ? 'bg-transparent' : 'bg-vp-bg-alt';
        $wrapperOpen = $dark ? '' : '<section class="'.$sectionBg.' py-20 body-font"><div class="mx-auto max-w-6xl px-6">';
        $wrapperClose = $dark ? '' : '</div></section>';

        return $wrapperOpen.$heading.'<div class="grid gap-6 md:grid-cols-2">'.$cards.'</div>'.$wrapperClose;
    }

    /**
     * @param  list<string>  $items
     */
    protected static function splitSection(string $title, string $image, array $items): string
    {
        $list = '';

        foreach ($items as $item) {
            $list .= '<li class="flex gap-3"><span class="text-vp-brand-1">✓</span><span>'.$item.'</span></li>';
        }

        return <<<HTML
<section class="bg-vp-bg py-20 body-font text-vp-text-2">
  <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-12 px-6">
    <img src="{$image}" alt="" class="w-full rounded-2xl object-cover shadow-xl lg:w-1/2 lg:h-80" />
    <div class="w-full lg:w-1/2">
      <h2 class="mb-6 text-3xl font-bold text-vp-text-1">{$title}</h2>
      <ul class="space-y-4 text-sm leading-relaxed">{$list}</ul>
    </div>
  </div>
</section>
HTML;
    }

    protected static function ctaSection(string $title, string $subtitle, string $href, string $label): string
    {
        return <<<HTML
<section class="bg-vp-brand-1 py-20 text-center body-font text-white">
  <div class="mx-auto max-w-3xl px-6">
    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{$title}</h2>
    <p class="mt-4 text-base leading-relaxed text-white/85">{$subtitle}</p>
    <a href="{$href}" class="mt-8 inline-flex rounded-lg bg-white px-8 py-3 text-sm font-semibold text-vp-brand-1 hover:bg-vp-bg-alt">{$label}</a>
  </div>
</section>
HTML;
    }
}
