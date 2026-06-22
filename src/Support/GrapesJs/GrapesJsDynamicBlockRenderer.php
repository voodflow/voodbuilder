<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMNode;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Vevents\Support\EventRichContentContext;
use Voodflow\Vpress\Models\SitePage;

final class GrapesJsDynamicBlockRenderer
{
    public function __construct(
        protected GrapesJsDynamicBlockRegistry $registry,
        protected GrapesJsServerBlockRegistry $serverRegistry,
    ) {}

    public function render(string $html, SitePage $page): string
    {
        if (blank($html) || ! str_contains($html, 'data-vpress-block')) {
            return $html;
        }

        $eventId = null;

        if (class_exists(EventRichContentContext::class)) {
            try {
                $eventId = EventRichContentContext::resolveEventIdForPageSlug((string) $page->slug);
            } catch (\Throwable) {
                $eventId = null;
            }
        }

        if ($eventId !== null) {
            $html = EventRichContentContext::injectEventIdIntoGrapesJsHtml($html, $eventId);
        }

        $document = $this->loadDocument($html);
        $renderData = GrapesJsRichContentBlockAdapter::renderData($eventId);

        foreach ($this->dynamicNodes($document) as $node) {
            $this->replaceNode($document, $node, $eventId, $renderData);
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
            if ($element instanceof DOMElement && $element->hasAttribute('data-vpress-block')) {
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
    ): void {
        $blockId = (string) $node->getAttribute('data-vpress-block');
        $config = $this->decodeConfig((string) $node->getAttribute('data-vpress-config'));

        if ($eventId !== null && GrapesJsDefaultBlockConfig::needsEventId($blockId) && empty($config['event_id'])) {
            $config['event_id'] = $eventId;
        }

        if (SiteFooterBlocks::isFooterBlockId($blockId) && $this->hasDynamicSlots($node)) {
            GrapesJsSlotHydrator::hydrateSubtree($document, $node, false);

            return;
        }

        $rendered = $this->renderBlockId($blockId, $config, $renderData);

        if ($rendered === null) {
            return;
        }

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
    protected function renderBlockId(string $blockId, array $config, array $data): ?string
    {
        $richBlockClass = $this->registry->resolve($blockId);

        if ($richBlockClass !== null) {
            return TailwindV4ClassMigrator::migrateHtml($richBlockClass::toHtml($config, $data));
        }

        $serverBlockClass = $this->serverRegistry->resolve($blockId);

        if ($serverBlockClass !== null) {
            $mergedConfig = $config !== [] ? $config : $serverBlockClass::defaultConfig();

            return TailwindV4ClassMigrator::migrateHtml($serverBlockClass::toHtml($mergedConfig, $data));
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

        return GrapesJsDynamicBlockAttributeNormalizer::decodeConfig($raw);
    }

    protected function hasDynamicSlots(DOMElement $node): bool
    {
        foreach ($node->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && (
                $element->hasAttribute('data-vpress-menu')
                || $element->hasAttribute('data-vpress-brand')
            )) {
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
