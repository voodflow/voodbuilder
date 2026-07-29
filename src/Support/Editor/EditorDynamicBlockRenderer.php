<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMDocument;
use DOMElement;
use DOMNode;
use Voodflow\Vevents\Support\EventRichContentContext;
use Voodflow\Voodbuilder\Models\SitePage;

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
        $renderData = EditorRichContentBlockAdapter::renderData($eventId);

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

        $authorStructuralClasses = SiteFooterBlocks::isFooterBlockId($blockId)
            ? $this->captureAuthorStructuralClasses($node)
            : null;

        $rendered = $this->renderBlockId($blockId, $config, $renderData, $canvasPreview);

        if ($rendered === null) {
            return;
        }

        if ($canvasPreview) {
            $this->replaceNodeInnerHtmlForEditor($document, $node, $rendered);
            if ($authorStructuralClasses !== null) {
                $this->restoreAuthorStructuralClasses($node, $authorStructuralClasses);
            }

            return;
        }

        $rendered = $this->stripPublishedBlockWrapperAttributes($rendered);

        if ($authorStructuralClasses !== null) {
            $rendered = $this->applyAuthorStructuralClassesToHtml($rendered, $authorStructuralClasses);
        }

        $this->replaceNodeWithRenderedHtml($document, $node, $rendered);
    }

    /**
     * Keep data-voodbuilder-block / config on the original node for the visual editor.
     * Published HTML still strips those attributes (see stripPublishedBlockWrapperAttributes).
     */
    protected function replaceNodeInnerHtmlForEditor(DOMDocument $document, DOMElement $node, string $rendered): void
    {
        $innerHtml = $this->unwrapEditorPreviewRoot($rendered);

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
