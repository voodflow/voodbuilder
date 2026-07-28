<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMNode;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

final class GrapesJsSlotHydrator
{
    public static function hydrateSubtree(DOMDocument $document, DOMElement $root, bool $preview = false, array $config = []): void
    {
        self::hydrateBrands($document, $root, $preview, $config);
        self::hydrateMenus($document, $root, $preview);

        if ($config !== []) {
            $normalized = SiteFooterConfig::normalize($config);
            self::applyFooterChromeVisibility($document, $root, $normalized, $preview);
            self::applyFooterColumnsVisibility($document, $root, $normalized, $preview);
        }
    }

    public static function hydrateHtml(string $html, bool $preview = false, array $config = []): string
    {
        if ($html === '' || (! str_contains($html, 'data-voodbuilder-menu') && ! str_contains($html, 'data-voodbuilder-brand') && ! str_contains($html, 'data-voodbuilder-chrome'))) {
            return $html;
        }

        $document = self::loadDocument($html);

        self::hydrateBrands($document, $document->documentElement, $preview, $config);
        self::hydrateMenus($document, $document->documentElement, $preview);

        if ($config !== []) {
            $normalized = SiteFooterConfig::normalize($config);
            self::applyFooterChromeVisibility($document, $document->documentElement, $normalized, $preview);
            self::applyFooterColumnsVisibility($document, $document->documentElement, $normalized, $preview);
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    public static function renderBrand(bool $preview = false, array $config = []): string
    {
        $normalized = SiteFooterConfig::normalize($config);

        return view('voodbuilder::grapesjs.blocks.partials.footer-brand', [
            'brandName' => VoodbuilderSettings::brandName(),
            'config' => $normalized,
            'showBrand' => (bool) ($normalized['show_brand'] ?? true),
            'showSiteName' => (bool) ($normalized['show_site_name'] ?? true),
            'preview' => $preview,
        ])->render();
    }

    public static function renderMenuList(string $menuSlug, bool $preview = false): string
    {
        if ($menuSlug === 'social') {
            return view('voodbuilder::grapesjs.blocks.partials.social-menu-list-wrapper', [
                'menuSlug' => $menuSlug,
                'preview' => $preview,
            ])->render();
        }

        return view('voodbuilder::grapesjs.blocks.partials.footer-menu-list-wrapper', [
            'menuSlug' => $menuSlug,
            'preview' => $preview,
        ])->render();
    }

    protected static function hydrateBrands(DOMDocument $document, DOMElement $root, bool $preview, array $config = []): void
    {
        $elements = [];

        foreach ($root->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-brand')) {
                $elements[] = $element;
            }
        }

        foreach ($elements as $element) {
            $slotConfig = self::resolveConfigForElement($element, $config);
            self::replaceElementInnerHtml($document, $element, self::renderBrand($preview, $slotConfig));
            // Prevent a later hydrateHtml() pass (e.g. ChromeLayoutRenderer) from
            // re-rendering the brand with an empty config and wiping custom logos.
            $element->removeAttribute('data-voodbuilder-brand');
        }
    }

    /**
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    protected static function resolveConfigForElement(DOMElement $element, array $fallback = []): array
    {
        $current = $element;

        while ($current instanceof DOMElement) {
            if ($current->hasAttribute('data-voodbuilder-config')) {
                $decoded = GrapesJsDynamicBlockAttributeNormalizer::decodeConfig(
                    (string) $current->getAttribute('data-voodbuilder-config'),
                );

                if ($decoded !== []) {
                    return $decoded;
                }
            }

            $parent = $current->parentNode;
            $current = $parent instanceof DOMElement ? $parent : null;
        }

        return $fallback;
    }

    protected static function hydrateMenus(DOMDocument $document, DOMElement $root, bool $preview): void
    {
        foreach ($root->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-voodbuilder-menu')) {
                continue;
            }

            $menuSlug = (string) $element->getAttribute('data-voodbuilder-menu');

            if ($menuSlug === '') {
                continue;
            }

            self::replaceElementInnerHtml($document, $element, self::renderMenuList($menuSlug, $preview));
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function applyFooterChromeVisibility(
        DOMDocument $document,
        DOMElement $root,
        array $config,
        bool $preview,
    ): void {
        foreach ($root->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-voodbuilder-chrome')) {
                continue;
            }

            $kind = (string) $element->getAttribute('data-voodbuilder-chrome');
            $visible = SiteFooterConfig::isChromeVisible($config, $kind);

            if ($preview) {
                // Canvas preview must not use Tailwind `hidden` — the editor toggles
                // data-voodbuilder-chrome-hidden only (layout + page editors).
                self::toggleElementClass($element, 'hidden', false);

                if ($visible) {
                    $element->removeAttribute('data-voodbuilder-chrome-hidden');
                } else {
                    $element->setAttribute('data-voodbuilder-chrome-hidden', '');
                }

                continue;
            }

            $element->removeAttribute('data-voodbuilder-chrome-hidden');
            self::toggleElementClass($element, 'hidden', ! $visible);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function applyFooterColumnsVisibility(
        DOMDocument $document,
        DOMElement $root,
        array $config,
        bool $preview = false,
    ): void {
        foreach ($root->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-voodbuilder-footer-col')) {
                continue;
            }

            $index = (int) $element->getAttribute('data-voodbuilder-footer-col');
            $visible = SiteFooterConfig::isFooterColumnVisible($config, $index);

            if ($preview) {
                // Editor canvas uses data-voodbuilder-chrome-hidden (see applyFooterChromeVisibility).
                // Never stamp Tailwind `hidden` in preview — settings UI only toggles the
                // chrome-hidden attribute and would leave `hidden` stuck after reload.
                self::toggleElementClass($element, 'hidden', false);

                continue;
            }

            self::toggleElementClass($element, 'hidden', ! $visible);
        }
    }

    protected static function toggleElementClass(DOMElement $element, string $className, bool $add): void
    {
        $classes = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];
        $classes = array_values(array_filter($classes, static fn (string $class): bool => $class !== ''));

        if ($add) {
            if (! in_array($className, $classes, true)) {
                $classes[] = $className;
            }
        } else {
            $classes = array_values(array_filter(
                $classes,
                static fn (string $class): bool => $class !== $className,
            ));
        }

        if ($classes === []) {
            $element->removeAttribute('class');
        } else {
            $element->setAttribute('class', implode(' ', $classes));
        }
    }

    protected static function replaceElementInnerHtml(DOMDocument $document, DOMElement $element, string $html): void
    {
        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }

        if (trim($html) === '') {
            return;
        }

        $fragmentDocument = self::loadDocument($html);
        $body = $fragmentDocument->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return;
        }

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMNode) {
                $element->appendChild($document->importNode($child, true));
            }
        }
    }

    protected static function loadDocument(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    protected static function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
