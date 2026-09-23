<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMDocument;
use DOMElement;
use DOMNode;
use Voodflow\Vevents\Support\EventRichContentContext;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Editor Dynamic Block Renderer.
 */
final class EditorDynamicBlockRenderer
{
    public function __construct(
        protected EditorDynamicBlockRegistry $registry,
        protected EditorServerBlockRegistry $serverRegistry,
    ) {}

    public function render(string $html, ?SitePage $page = null, bool $canvasPreview = false): string
    {
        if (blank($html) || ! str_contains($html, 'data-voodbuilder-block')) {
            return $html;
        }

        $eventId = null;

        if ($page !== null && class_exists(EventRichContentContext::class)) {
            try {
                $eventId = EventRichContentContext::resolveEventIdForPageSlug((string) $page->slug);
            } catch (\Throwable) {
                $eventId = null;
            }
        }

        if ($eventId !== null) {
            $html = EventRichContentContext::injectEventIdIntoEditorHtml($html, $eventId);
        }

        $document = $this->loadDocument($html);
        $renderData = EditorRichContentBlockAdapter::renderData($eventId, $page);

        foreach ($this->dynamicNodes($document) as $node) {
            $this->replaceNode($document, $node, $eventId, $renderData, $canvasPreview);
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    protected function loadDocument(string $html): DOMDocument
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

    /**
     * @return list<DOMElement>
     */
    protected function dynamicNodes(DOMDocument $document): array
    {
        $nodes = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-block')) {
                $nodes[] = $element;
            }
        }

        return $nodes;
    }

    /**
     * @param  array<string, mixed>  $renderData
     */
    protected function replaceNode(
        DOMDocument $document,
        DOMElement $node,
        ?int $eventId,
        array $renderData,
        bool $canvasPreview = false,
    ): void {
        $blockId = (string) $node->getAttribute('data-voodbuilder-block');
        $config = $this->decodeConfig((string) $node->getAttribute('data-voodbuilder-config'));

        if ($eventId !== null && EditorDefaultBlockConfig::needsEventId($blockId) && empty($config['event_id'])) {
            $config['event_id'] = $eventId;
        }

        /*
         * Prefer slot hydration for footers that already have saved markup.
         * Full Blade re-render wipes author classes on wrappers (e.g. py-16 on
         * .voodbuilder-editor-container) and breaks layout-editor persistence.
         */
        if (
            SiteFooterBlocks::isFooterBlockId($blockId)
            && $this->hasDynamicSlots($node)
            && ! $this->footerNeedsFullRender($node, $blockId)
        ) {
            EditorSlotHydrator::hydrateSubtree($document, $node, $canvasPreview, $config);

            if (! $canvasPreview) {
                $this->stripPublishedAttributesFromNode($node);
            }

            return;
        }

        // Nested footer/progress inside nav are destroyed by innerHTML replace —
        // lift them to siblings first (same idea as ChromeLayoutHtmlSanitizer).
        if (SiteNavBlocks::isNavBlockId($blockId)) {
            $this->hoistMisplacedChromeBlocksFromNav($document, $node);
        }

        $authorStructuralClasses = $this->captureAuthorStructuralClasses($node);
        $authorContentWidthShells = $this->captureAuthorContentWidthShells($node);
        $authorChromeMenuSlots = $this->captureAuthorChromeMenuSlotClasses($node);
        $hiddenLayerNames = $this->captureHiddenLayerNames($node);

        // Core nodes: Layers eye-hide on "Custom Nodes" must flip Blade config so
        // remount does not resurrect the card on the published page.
        if ($blockId === 'voodflow_core_nodes_grid' && $this->layerNamesIncludeCustomNodes($hiddenLayerNames, $config)) {
            $config['show_custom_nodes_card'] = false;
            $node->setAttribute(
                'data-voodbuilder-config',
                EditorDynamicBlockAttributeNormalizer::encodeConfig($config),
            );
        }

        $rendered = $this->renderBlockId($blockId, $config, $renderData, $canvasPreview);

        if ($rendered === null) {
            return;
        }

        $rendered = $this->applyHiddenLayerNamesToHtml($rendered, $hiddenLayerNames);

        if ($canvasPreview) {
            $this->replaceNodeInnerHtmlForEditor($document, $node, $rendered);
            if ($authorStructuralClasses !== null) {
                $this->restoreAuthorStructuralClasses($node, $authorStructuralClasses);
            }
            $this->restoreAuthorContentWidthShells($node, $authorContentWidthShells);
            $this->restoreAuthorChromeMenuSlotClasses($node, $authorChromeMenuSlots);

            return;
        }

        $rendered = $this->stripPublishedBlockWrapperAttributes($rendered);

        if ($authorStructuralClasses !== null) {
            $rendered = $this->applyAuthorStructuralClassesToHtml($rendered, $authorStructuralClasses);
        }

        $rendered = $this->applyAuthorContentWidthShellsToHtml($rendered, $authorContentWidthShells);
        $rendered = $this->applyAuthorChromeMenuSlotClassesToHtml($rendered, $authorChromeMenuSlots);

        $this->replaceNodeWithRenderedHtml($document, $node, $rendered);
    }

    /**
     * Lift footer / reading-progress nodes nested under a nav block so a Blade
     * remount cannot wipe them (layout editor save/load regression).
     */
    protected function hoistMisplacedChromeBlocksFromNav(DOMDocument $document, DOMElement $nav): void
    {
        $parent = $nav->parentNode;

        if ($parent === null) {
            return;
        }

        $toHoist = [];

        foreach ($nav->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            if ($this->isHoistableChromeElement($element)) {
                $toHoist[] = $element;
            }
        }

        foreach ($toHoist as $element) {
            if ($element->parentNode === null || ! $nav->contains($element)) {
                continue;
            }

            $parent->insertBefore($element, $nav->nextSibling);
        }
    }

    protected function isHoistableChromeElement(DOMElement $element): bool
    {
        if ($element->hasAttribute('data-voodbuilder-block')) {
            $blockId = (string) $element->getAttribute('data-voodbuilder-block');

            return SiteFooterBlocks::isFooterBlockId($blockId)
                || $blockId === 'voodbuilder-reading-progress'
                || str_contains($blockId, 'reading-progress');
        }

        if ($element->hasAttribute('data-voodbuilder-progress') || $element->hasAttribute('data-reading-progress')) {
            return true;
        }

        $class = ' '.trim((string) $element->getAttribute('class')).' ';

        return str_contains($class, ' vb-reading-progress ');
    }

    /**
     * Keep data-voodbuilder-block / config on the original node for the visual editor.
     * Published HTML still strips those attributes (see stripPublishedBlockWrapperAttributes).
     */
    protected function replaceNodeInnerHtmlForEditor(DOMDocument $document, DOMElement $node, string $rendered): void
    {
        $innerHtml = $this->unwrapMatchingRootTag(
            $node,
            $this->unwrapEditorPreviewRoot($rendered),
        );

        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }

        $class = trim((string) $node->getAttribute('class'));

        if ($class === '' || ! str_contains($class, 'voodbuilder-editor-dynamic')) {
            $node->setAttribute('class', trim($class.' voodbuilder-editor-dynamic'));
        }

        if ($innerHtml === '') {
            return;
        }

        $fragmentDocument = $this->loadDocument($innerHtml);
        $body = $fragmentDocument->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return;
        }

        foreach (iterator_to_array($body->childNodes) as $child) {
            if ($child instanceof DOMNode) {
                $node->appendChild($document->importNode($child, true));
            }
        }
    }

    /**
     * When the dynamic root is already a section/footer (smart wrap), the Blade
     * preview returns the same outer tag — use its children to avoid section > section.
     */
    protected function unwrapMatchingRootTag(DOMElement $node, string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $tag = strtolower($node->tagName);

        if (! in_array($tag, ['section', 'footer', 'header', 'article', 'div'], true)) {
            return $html;
        }

        $document = $this->loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        $elementChildren = [];

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $elementChildren[] = $child;
            }
        }

        if (count($elementChildren) !== 1 || strtolower($elementChildren[0]->tagName) !== $tag) {
            return $html;
        }

        $output = '';

        foreach ($elementChildren[0]->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    /**
     * If preview HTML already includes a block wrapper, use its children to avoid nesting.
     */
    protected function unwrapEditorPreviewRoot(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-block')) {
            return $html;
        }

        $document = $this->loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        $elementChildren = [];

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $elementChildren[] = $child;
            }
        }

        if (count($elementChildren) !== 1 || ! $elementChildren[0]->hasAttribute('data-voodbuilder-block')) {
            return $this->stripPublishedBlockWrapperAttributes($html);
        }

        $output = '';

        foreach ($elementChildren[0]->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    protected function stripPublishedBlockWrapperAttributes(string $html): string
    {
        if (! str_contains($html, 'data-voodbuilder-block')) {
            return $html;
        }

        $document = $this->loadDocument($html);

        foreach ($this->dynamicNodes($document) as $node) {
            $this->stripPublishedAttributesFromNode($node);
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    protected function stripPublishedAttributesFromNode(DOMElement $node): void
    {
        $node->removeAttribute('data-voodbuilder-block');
        $node->removeAttribute('data-voodbuilder-config');
        $node->removeAttribute('data-voodbuilder-hydrate-slots');

        // Brand/menu slots are already filled; drop markers so a later
        // ChromeLayoutRenderer hydrateHtml() pass cannot wipe custom logos.
        foreach ($node->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            if ($element->hasAttribute('data-voodbuilder-brand')) {
                $element->removeAttribute('data-voodbuilder-brand');
            }
        }
    }

    protected function isColumnFooterBlock(string $blockId): bool
    {
        return in_array($blockId, ['site_footer_columns_simple', 'site_footer_columns_newsletter'], true);
    }

    /**
     * @return list<array{content_width: string, class: string, style: string}>
     */
    protected function captureAuthorContentWidthShells(DOMElement $node): array
    {
        $shells = [];

        foreach ($this->contentWidthShellElements($node) as $element) {
            $shells[] = [
                'content_width' => trim((string) $element->getAttribute('data-voodbuilder-content-width')),
                'class' => trim((string) $element->getAttribute('class')),
                'style' => trim((string) $element->getAttribute('style')),
            ];
        }

        return $shells;
    }

    /**
     * @param  list<array{content_width: string, class: string, style: string}>  $shells
     */
    protected function restoreAuthorContentWidthShells(DOMElement $node, array $shells): void
    {
        if ($shells === []) {
            return;
        }

        $index = 0;

        foreach ($this->contentWidthShellElements($node) as $element) {
            if (! isset($shells[$index])) {
                break;
            }

            $this->applyContentWidthShellToElement($element, $shells[$index]);
            $index++;
        }
    }

    /**
     * @param  list<array{content_width: string, class: string, style: string}>  $shells
     */
    protected function applyAuthorContentWidthShellsToHtml(string $html, array $shells): string
    {
        if ($html === '' || $shells === []) {
            return $html;
        }

        $document = $this->loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        $index = 0;

        foreach ($this->contentWidthShellElements($body) as $element) {
            if (! isset($shells[$index])) {
                break;
            }

            $this->applyContentWidthShellToElement($element, $shells[$index]);
            $index++;
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    /**
     * @param  array{content_width: string, class: string, style: string}  $shell
     */
    protected function applyContentWidthShellToElement(DOMElement $element, array $shell): void
    {
        if ($shell['content_width'] !== '') {
            $element->setAttribute('data-voodbuilder-content-width', $shell['content_width']);
        }

        if ($shell['class'] !== '') {
            $element->setAttribute(
                'class',
                $this->mergeAuthorContentWidthClasses(
                    $shell['class'],
                    trim((string) $element->getAttribute('class')),
                ),
            );
        }

        if ($shell['style'] !== '') {
            $element->setAttribute(
                'style',
                $this->mergeAuthorContentWidthStyles(
                    $shell['style'],
                    trim((string) $element->getAttribute('style')),
                ),
            );
        }
    }

    /**
     * @return list<DOMElement>
     */
    protected function contentWidthShellElements(DOMNode $root): array
    {
        $shells = [];
        $document = $root instanceof DOMDocument ? $root : $root->ownerDocument;

        if ($document === null) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $query = './/*[contains(concat(" ", normalize-space(@class), " "), " voodbuilder-editor-container ")'
            .' or @data-voodbuilder-role="content"'
            .' or contains(concat(" ", normalize-space(@class), " "), " container ")]';

        foreach ($xpath->query($query, $root) as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            // Skip nested containers that are layout grid cells — only real measure shells.
            $class = trim((string) $element->getAttribute('class'));

            if (str_contains($class, 'vb-layout-block') || str_contains($class, 'voodbuilder-hero-media')) {
                continue;
            }

            $shells[] = $element;
        }

        return $shells;
    }

    protected function mergeAuthorContentWidthClasses(string $author, string $fresh): string
    {
        $authorTokens = preg_split('/\s+/', trim($author)) ?: [];
        $freshTokens = preg_split('/\s+/', trim($fresh)) ?: [];
        $measureTokens = ['mx-auto', 'max-w-[80rem]', 'max-w-none', 'w-full'];
        $merged = $freshTokens;

        foreach ($authorTokens as $token) {
            if ($token === '') {
                continue;
            }

            if (
                in_array($token, $measureTokens, true)
                || str_starts_with($token, 'max-w-[')
            ) {
                if (! in_array($token, $merged, true)) {
                    $merged[] = $token;
                }
            }
        }

        return trim(implode(' ', array_values(array_unique($merged))));
    }

    protected function mergeAuthorContentWidthStyles(string $author, string $fresh): string
    {
        $keys = ['width', 'max-width', 'margin-left', 'margin-right', 'margin-inline'];
        $parsed = [];

        foreach ([$fresh, $author] as $style) {
            foreach (explode(';', $style) as $part) {
                $part = trim($part);

                if ($part === '' || ! str_contains($part, ':')) {
                    continue;
                }

                [$property, $value] = array_map('trim', explode(':', $part, 2));
                $propertyLower = strtolower($property);

                if (! in_array($propertyLower, $keys, true)) {
                    if (! array_key_exists($propertyLower, $parsed) && $style === $fresh) {
                        $parsed[$propertyLower] = $property.': '.$value;
                    }

                    continue;
                }

                // Author measure wins over fresh defaults.
                if ($style === $author) {
                    $parsed[$propertyLower] = $property.': '.$value;
                } elseif (! array_key_exists($propertyLower, $parsed)) {
                    $parsed[$propertyLower] = $property.': '.$value;
                }
            }
        }

        return implode('; ', array_values($parsed));
    }

    /**
     * Stable keys matching JS captureChromeMenuSlotAuthorClasses so typography
     * utilities (e.g. uppercase) survive Blade remount of site_nav_* blocks.
     *
     * @return array<string, array{class: string, style: string}>
     */
    protected function captureAuthorChromeMenuSlotClasses(DOMElement $node): array
    {
        $byKey = [];
        $desktopNavIndex = 0;

        foreach ($this->chromeMenuSlotElements($node) as $element) {
            $key = $this->chromeMenuSlotAuthorKey($element, $desktopNavIndex);

            if ($element->hasAttribute('data-voodbuilder-desktop-nav')) {
                $desktopNavIndex++;
            }

            $byKey[$key] = [
                'class' => trim((string) $element->getAttribute('class')),
                'style' => trim((string) $element->getAttribute('style')),
            ];
        }

        return $byKey;
    }

    /**
     * @param  array<string, array{class: string, style: string}>  $authorByKey
     */
    protected function restoreAuthorChromeMenuSlotClasses(DOMElement $node, array $authorByKey): void
    {
        if ($authorByKey === []) {
            return;
        }

        $desktopNavIndex = 0;

        foreach ($this->chromeMenuSlotElements($node) as $element) {
            $key = $this->chromeMenuSlotAuthorKey($element, $desktopNavIndex);

            if ($element->hasAttribute('data-voodbuilder-desktop-nav')) {
                $desktopNavIndex++;
            }

            if (! isset($authorByKey[$key])) {
                continue;
            }

            $this->applyChromeMenuSlotAuthorToElement($element, $authorByKey[$key]);
        }
    }

    /**
     * @param  array<string, array{class: string, style: string}>  $authorByKey
     */
    protected function applyAuthorChromeMenuSlotClassesToHtml(string $html, array $authorByKey): string
    {
        if ($html === '' || $authorByKey === []) {
            return $html;
        }

        $document = $this->loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->restoreAuthorChromeMenuSlotClasses($child, $authorByKey);
            }
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    /**
     * @param  array{class: string, style: string}  $author
     */
    protected function applyChromeMenuSlotAuthorToElement(DOMElement $element, array $author): void
    {
        if ($author['class'] !== '') {
            $element->setAttribute(
                'class',
                $this->mergeAuthorChromeMenuSlotClasses(
                    $author['class'],
                    trim((string) $element->getAttribute('class')),
                ),
            );
        }

        if ($author['style'] !== '') {
            $element->setAttribute(
                'style',
                $this->mergeAuthorContentWidthStyles(
                    $author['style'],
                    trim((string) $element->getAttribute('style')),
                ),
            );
        }
    }

    protected function mergeAuthorChromeMenuSlotClasses(string $author, string $fresh): string
    {
        $merged = [];
        $seen = [];

        foreach ([...preg_split('/\s+/', trim($author)) ?: [], ...preg_split('/\s+/', trim($fresh)) ?: []] as $token) {
            if ($token === '' || isset($seen[$token])) {
                continue;
            }

            $seen[$token] = true;
            $merged[] = $token;
        }

        return implode(' ', $merged);
    }

    protected function chromeMenuSlotAuthorKey(DOMElement $element, int $desktopNavIndex): string
    {
        if ($element->hasAttribute('data-voodbuilder-menu')) {
            return 'menu:'.trim((string) $element->getAttribute('data-voodbuilder-menu'));
        }

        if ($element->hasAttribute('data-voodbuilder-desktop-nav')) {
            return 'desktop-nav:'.$desktopNavIndex;
        }

        return 'mobile-links';
    }

    /**
     * @return list<DOMElement>
     */
    protected function chromeMenuSlotElements(DOMNode $root): array
    {
        $slots = [];
        $document = $root instanceof DOMDocument ? $root : $root->ownerDocument;

        if ($document === null) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $query = './/*[@data-voodbuilder-desktop-nav or @data-voodbuilder-menu'
            .' or contains(concat(" ", normalize-space(@class), " "), " voodbuilder-mobile-nav__links ")]';

        foreach ($xpath->query($query, $root) as $element) {
            if ($element instanceof DOMElement) {
                $slots[] = $element;
            }
        }

        return $slots;
    }

    /**
     * Layer names eye-hidden by the author (data-vb-layer-hidden or display:none).
     *
     * @return list<string>
     */
    protected function captureHiddenLayerNames(DOMElement $node): array
    {
        $names = [];

        foreach ($node->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            $name = trim((string) $element->getAttribute('data-voodbuilder-layer-name'));

            if ($name === '') {
                continue;
            }

            if ($element->getAttribute('data-vb-layer-hidden') === '1') {
                $names[] = $name;

                continue;
            }

            $style = strtolower(trim((string) $element->getAttribute('style')));

            if ($style !== '' && preg_match('/(^|;)\s*display\s*:\s*none\b/', $style) === 1) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  list<string>  $names
     * @param  array<string, mixed>  $config
     */
    protected function layerNamesIncludeCustomNodes(array $names, array $config): bool
    {
        $customTitle = strtolower(trim((string) ($config['custom_nodes_title'] ?? 'Custom Nodes')));

        foreach ($names as $name) {
            $normalized = strtolower(trim((string) $name));

            if ($normalized === 'custom nodes' || ($customTitle !== '' && $normalized === $customTitle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Re-apply Layers eye-hide after Blade remount (match by data-voodbuilder-layer-name).
     *
     * @param  list<string>  $names
     */
    protected function applyHiddenLayerNamesToHtml(string $html, array $names): string
    {
        if ($html === '' || $names === []) {
            return $html;
        }

        $wanted = [];

        foreach ($names as $name) {
            $trimmed = trim((string) $name);

            if ($trimmed !== '') {
                $wanted[strtolower($trimmed)] = true;
            }
        }

        if ($wanted === []) {
            return $html;
        }

        $document = $this->loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        foreach ($body->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            $name = trim((string) $element->getAttribute('data-voodbuilder-layer-name'));

            if ($name === '' || ! isset($wanted[strtolower($name)])) {
                continue;
            }

            $element->setAttribute('data-vb-layer-hidden', '1');
            $style = trim((string) $element->getAttribute('style'));
            $style = preg_replace('/(^|;)\s*display\s*:\s*[^;]+/i', '', $style) ?? $style;
            $style = trim($style, " \t\n\r\0\x0B;");
            $element->setAttribute('style', $style === '' ? 'display:none' : $style.';display:none');
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    /**
     * @return array{root: string, containers: list<string>}
     */
    protected function captureAuthorStructuralClasses(DOMElement $node): array
    {
        $containers = [];

        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $class = trim((string) $child->getAttribute('class'));

            if ($class === '') {
                continue;
            }

            if (
                str_contains($class, 'voodbuilder-editor-container')
                || preg_match('/(^|\s)container(\s|$)/', $class) === 1
            ) {
                $containers[] = $class;
            }
        }

        return [
            'root' => trim((string) $node->getAttribute('class')),
            'containers' => $containers,
        ];
    }

    /**
     * @param  array{root: string, containers: list<string>}  $author
     */
    protected function restoreAuthorStructuralClasses(DOMElement $node, array $author): void
    {
        if ($author['root'] !== '') {
            $node->setAttribute('class', $author['root']);
        }

        if ($author['containers'] === []) {
            return;
        }

        $index = 0;

        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $class = trim((string) $child->getAttribute('class'));

            if (
                ! str_contains($class, 'voodbuilder-editor-container')
                && preg_match('/(^|\s)container(\s|$)/', $class) !== 1
            ) {
                continue;
            }

            if (! isset($author['containers'][$index])) {
                break;
            }

            $child->setAttribute('class', $author['containers'][$index]);
            $index++;
        }
    }

    /**
     * @param  array{root: string, containers: list<string>}  $author
     */
    protected function applyAuthorStructuralClassesToHtml(string $html, array $author): string
    {
        if ($html === '' || ($author['root'] === '' && $author['containers'] === [])) {
            return $html;
        }

        $document = $this->loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        foreach ($body->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $this->restoreAuthorStructuralClasses($child, $author);
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    protected function replaceNodeWithRenderedHtml(DOMDocument $document, DOMElement $node, string $rendered): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            return;
        }

        $fragmentDocument = $this->loadDocument($rendered);
        $body = $fragmentDocument->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return;
        }

        $importedNodes = [];

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMNode) {
                $importedNodes[] = $document->importNode($child, true);
            }
        }

        foreach ($importedNodes as $imported) {
            $parent->insertBefore($imported, $node);
        }

        $parent->removeChild($node);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    protected function renderBlockId(string $blockId, array $config, array $data, bool $canvasPreview = false): ?string
    {
        $richBlockClass = $this->registry->resolve($blockId);

        if ($richBlockClass !== null) {
            $html = $canvasPreview
                ? EditorRichContentBlockAdapter::editorPreviewHtml($richBlockClass, $config)
                : $richBlockClass::toHtml($config, $data);

            return EditorRichContentBlockAdapter::prepareBlockHtml($html);
        }

        $serverBlockClass = $this->serverRegistry->resolve($blockId);

        if ($serverBlockClass !== null) {
            $mergedConfig = $config !== [] ? $config : $serverBlockClass::defaultConfig();
            $html = $canvasPreview
                ? $serverBlockClass::toPreviewHtml($mergedConfig, $data)
                : $serverBlockClass::toHtml($mergedConfig, $data);

            return EditorRichContentBlockAdapter::prepareBlockHtml($html);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeConfig(string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return EditorDynamicBlockAttributeNormalizer::decodeConfig($raw);
    }

    protected function hasDynamicSlots(DOMElement $node): bool
    {
        foreach ($node->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && (
                $element->hasAttribute('data-voodbuilder-menu')
                || $element->hasAttribute('data-voodbuilder-brand')
            )) {
                return true;
            }
        }

        return false;
    }

    protected function footerNeedsFullRender(DOMElement $node, string $blockId): bool
    {
        if (! $this->isColumnFooterBlock($blockId)) {
            return false;
        }

        $hasBrandColumn = false;
        $columnsUsePerColumnChrome = true;

        foreach ($node->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            if ($element->hasAttribute('data-voodbuilder-footer-brand-col')) {
                $hasBrandColumn = true;
            }

            if ($element->hasAttribute('data-voodbuilder-footer-col')
                && ! $element->hasAttribute('data-voodbuilder-chrome')) {
                $columnsUsePerColumnChrome = false;
            }
        }

        return ! $hasBrandColumn || ! $columnsUsePerColumnChrome || $this->footerHasLegacyInlineMenu($node);
    }

    protected function footerHasLegacyInlineMenu(DOMElement $node): bool
    {
        foreach ($node->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            if ($element->getAttribute('data-voodbuilder-menu') === 'footer'
                && $element->getAttribute('data-voodbuilder-chrome') === 'footer-menu') {
                return true;
            }
        }

        return false;
    }

    protected function extractBodyHtml(DOMDocument $document): ?string
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
