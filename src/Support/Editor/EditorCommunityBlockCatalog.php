<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Core Community Editor library allowlist.
 *
 * Premium section tiles live in `voodflow/voodbuilder-elements` (remote catalog).
 * {@see COMPANION_BLOCK_IDS} documents IDs owned by that companion after extract.
 * Sidebar visibility for Core-owned IDs is gated by {@see Voodbuilder::can}('blocks.official.complete').
 * Third-party / Elements packages that register new IDs (not in {@see coreOwnedIds()})
 * stay visible — injection must not break.
 *
 * @see docs/handoff/elements-companion-pack.md
 */
final class EditorCommunityBlockCatalog
{
    public const CAPABILITY_FULL_LIBRARY = 'blocks.official.complete';

    /**
     * Foundation categories — keep complete in Community.
     *
     * @var list<string>
     */
    public const FOUNDATION_BLOCK_IDS = [
        // Layout
        'voodbuilder-layout-section',
        'voodbuilder-layout-container',
        'voodbuilder-layout-block',
        'voodbuilder-layout-div',
        // Basic
        'voodbuilder-heading',
        'voodbuilder-text',
        'voodbuilder-rich-text',
        'voodbuilder-text-link',
        'voodbuilder-button',
        'voodbuilder-icon',
        'voodbuilder-divider',
        // Media
        'image',
        'video',
        'voodbuilder-image-gallery',
        'voodbuilder-audio',
        'voodbuilder-carousel',
        'voodbuilder-slider',
        // Single → Utilities
        'voodbuilder-reading-time',
        'voodbuilder-reading-progress',
        'voodbuilder-social-share',
        // Site (no chrome_content_slot — chrome layout editor only)
        'site_nav_simple',
        'site_footer_columns_simple',
        'site_footer_columns_newsletter',
        'site_footer_centered',
        'site_footer_social',
    ];

    /**
     * Free local section pack (~10): enough to assemble a full marketing template
     * without the remote Elements companion. Distinct from api.voodflow.com items.
     *
     * @var list<string>
     */
    public const COMMUNITY_SECTION_BLOCK_IDS = [
        'vb-hero-2',
        'vb-bg-image',
        'vb-content-5',
        'vb-feature-1',
        'vb-feature-2',
        'vb-testimonial-1',
        'vb-team-1',
        'vb-step-1',
        'vb-cta-1',
        'vb-landing02-faq',
        // Lightweight JS tiles kept local for Community demos
        'voodbuilder-animated-cta',
        'voodbuilder-animated-stats',
        'voodbuilder-tabs-pills',
    ];

    /**
     * Community JS tiles shown in the Core sidebar when Elements is not installed.
     * When Elements is active they move to the Library modal only.
     *
     * @var list<string>
     */
    public const COMMUNITY_JS_BLOCK_IDS = [
        'voodbuilder-animated-cta',
        'voodbuilder-animated-stats',
        'voodbuilder-tabs-pills',
    ];

    /**
     * Chrome-layout-only Site helper (on canvas via starter HTML; not a sidebar tile).
     */
    public const CHROME_ONLY_BLOCK_ID = 'chrome_content_slot';

    /**
     * Layout editor sidebar: header/footer chrome only (content slot is already on canvas).
     *
     * @var list<string>
     */
    public const CHROME_LAYOUT_SIDEBAR_BLOCK_IDS = [
        'site_nav_simple',
        'site_footer_columns_simple',
        'site_footer_columns_newsletter',
        'site_footer_centered',
        'site_footer_social',
    ];

    /**
     * Core-owned IDs that leave Community sidebar for the Elements companion / Pro library.
     *
     * @var list<string>
     */
    public const COMPANION_BLOCK_IDS = [
        // Hero extras
        'vb-hero-1', 'vb-hero-3', 'vb-hero-4', 'vb-hero-5', 'vb-hero-6',
        'vb-landing01-hero', 'vb-landing02-hero', 'vb-hero-cinematic', 'vb-bg-video',
        'vb-hero-plasma', 'vb-hero-aurora',
        'vb-voodflow-hero-aurora', 'vb-voodflow-product-grid',
        // Content extras
        'vb-content-1', 'vb-content-2', 'vb-content-3', 'vb-content-4',
        'vb-content-6', 'vb-content-7', 'vb-content-8',
        'vb-landing01-solution', 'vb-landing02-trust', 'vb-landing02-impact',
        'vb-content-teaser-card', 'vb-content-spotlight-split',
        // Features extras
        'vb-feature-3', 'vb-feature-4', 'vb-feature-5', 'vb-feature-6',
        'vb-feature-7', 'vb-feature-8',
        'vb-landing01-features', 'vb-landing02-features', 'vb-landing02-toolkit',
        // Articles extras (+ demoted from Community free pack)
        'vb-blog-1', 'vb-blog-2', 'vb-blog-3', 'vb-blog-4', 'vb-blog-5',
        'vb-landing01-articles', 'vb-landing02-articles', 'vb-articles-featured-stories', 'vb-articles-explore-cards',
        // Gallery (entire category)
        'vb-gallery-1', 'vb-gallery-2', 'vb-gallery-3',
        'vb-gallery-mission-cards', 'vb-slider-images', 'vb-slider-videos',
        // Stats (entire category)
        'vb-statistic-1', 'vb-statistic-2', 'vb-statistic-3', 'vb-stats-cinematic-band',
        'vb-stats-count-up', 'vb-stats-metrics-band',
        // Testimonials extras
        'vb-testimonial-2', 'vb-testimonial-3', 'vb-landing01-testimonials',
        // Team extras
        'vb-team-2', 'vb-team-3',
        // Steps extras
        'vb-step-2', 'vb-step-3',
        // Pricing (entire category)
        'vb-pricing-1', 'vb-pricing-2',
        // CTA extras
        'vb-cta-2', 'vb-cta-3', 'vb-cta-4', 'vb-landing01-cta',
        'vb-cta-glow-pulse', 'vb-cta-split-shimmer',
        // Contact (entire category)
        'vb-contact-1', 'vb-contact-2', 'vb-contact-3',
        // Shop (entire category)
        'vb-ecommerce-1', 'vb-ecommerce-2', 'vb-ecommerce-3',
        // Header / Footer section catalog (entire categories)
        'vb-header-1', 'vb-header-2', 'vb-header-3', 'vb-header-4',
        'vb-footer-1', 'vb-footer-2', 'vb-footer-3', 'vb-footer-4', 'vb-footer-5',
        // Animated extras
        'voodbuilder-animated-counter',
        'voodbuilder-logo-scroll',
        'voodbuilder-logo-grid',
        'voodbuilder-logo-split',
        // Tabs / Code / Forms (JS categories → companion; pills stays in Community)
        'voodbuilder-tabs-underline',
        'voodbuilder-tabs-segmented',
        'voodbuilder-code-block',
        'voodbuilder-form',
        'newsletter-form',
        // Unregistered Site nav variants (classes exist; companion may expose them)
        'site_nav_centered_links',
        'site_nav_with_action',
        'site_nav_with_search',
        'site_nav_menu_left',
    ];

    public static function limitsLibrary(): bool
    {
        return ! Voodbuilder::can(self::CAPABILITY_FULL_LIBRARY);
    }

    /**
     * True when the Elements companion plugin is active on this host.
     * Section templates then live in the remote SOURCE UI — not the local accordion.
     */
    public static function elementsLibraryActive(): bool
    {
        $class = 'Voodflow\\VoodbuilderElements\\VoodbuilderElements';

        if (! class_exists($class)) {
            return false;
        }

        return $class::isActivated() && $class::isEnabled();
    }

    /**
     * @return list<string>|null Null = no sidebar filter (full library).
     */
    public static function sidebarAllowlist(?bool $chromeLayoutEditor = null): ?array
    {
        $chromeLayoutEditor ??= self::requestIsChromeLayoutEditor();

        // Layout editor: only nav/footer SITE blocks — never landing sections or the
        // page-content slot tile (slot is already injected in starter HTML).
        if ($chromeLayoutEditor) {
            return self::CHROME_LAYOUT_SIDEBAR_BLOCK_IDS;
        }

        // Elements companion active → SOURCE / Library owns section templates.
        // Keep only foundation tiles in the left accordion (Layout, Basic, Media,
        // Utilities, Site). Free marketing sections would duplicate the remote catalog.
        if (self::elementsLibraryActive()) {
            return self::FOUNDATION_BLOCK_IDS;
        }

        // No Elements plugin: limited local library — foundation + free section pack
        // (and soft upsell in the Library panel). Pro capability alone does not dump
        // the full remote catalog into the accordion; that requires Elements.
        return array_values(array_unique([
            ...self::FOUNDATION_BLOCK_IDS,
            ...self::COMMUNITY_SECTION_BLOCK_IDS,
        ]));
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function filterEditorBlocks(array $blocks, ?bool $chromeLayoutEditor = null): array
    {
        $chromeLayoutEditor ??= self::requestIsChromeLayoutEditor();
        $excluded = array_map('strval', (array) config('voodbuilder.editor.excluded_editor_blocks', []));

        $allowlist = self::sidebarAllowlist($chromeLayoutEditor);
        $coreOwned = array_fill_keys(self::coreOwnedIds(), true);
        $chromeSidebar = array_fill_keys(self::CHROME_LAYOUT_SIDEBAR_BLOCK_IDS, true);

        return array_values(array_filter(
            $blocks,
            static function (array $block) use ($excluded, $allowlist, $coreOwned, $chromeLayoutEditor, $chromeSidebar): bool {
                $id = (string) ($block['id'] ?? '');

                if ($id === '' || in_array($id, $excluded, true)) {
                    return false;
                }

                // Content slot stays on the canvas via starter HTML — never a sidebar tile.
                if ($id === self::CHROME_ONLY_BLOCK_ID) {
                    return false;
                }

                if ($chromeLayoutEditor) {
                    return isset($chromeSidebar[$id]);
                }

                // Full library: everything except chrome slot.
                if ($allowlist === null) {
                    return true;
                }

                if (in_array($id, $allowlist, true)) {
                    return true;
                }

                // Official companion catalog IDs belong to Elements / Pro — never in Community sidebar.
                if (self::isCompanionBlockId($id)) {
                    return false;
                }

                // Unknown third-party IDs stay visible.
                return ! isset($coreOwned[$id]);
            },
        ));
    }

    /**
     * @return list<string>
     */
    public static function coreOwnedIds(): array
    {
        // After Elements extract, companion IDs are no longer Core-owned.
        // When the Elements plugin registers them they pass the Community filter
        // as third-party injection (visible only because they are registered).
        return array_values(array_unique([
            ...self::FOUNDATION_BLOCK_IDS,
            ...self::COMMUNITY_SECTION_BLOCK_IDS,
            self::CHROME_ONLY_BLOCK_ID,
        ]));
    }

    public static function isCompanionBlockId(string $id): bool
    {
        return in_array($id, self::COMPANION_BLOCK_IDS, true);
    }

    public static function requestIsChromeLayoutEditor(): bool
    {
        $request = request();

        return $request->boolean('chrome')
            || $request->boolean('chrome_layout')
            || (bool) $request->attributes->get('voodbuilder_chrome_layout_editor', false);
    }
}
