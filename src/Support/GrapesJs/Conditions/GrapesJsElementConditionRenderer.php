<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Conditions;

use DOMDocument;
use DOMElement;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\Conditions\ConditionsModule;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate;

final class GrapesJsElementConditionRenderer
{
    public function __construct(
        private readonly GrapesJsElementConditionEvaluator $evaluator,
    ) {}

    public function render(string $html, ?SitePage $page = null): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-conditions')) {
            return $html;
        }

        if ($page !== null && GrapesJsEditorGate::isEditing($page)) {
            return $html;
        }

        // When the Conditions module is disabled, ignore rules and keep content visible.
        if (! ConditionsModule::isEnabled()) {
            return $this->stripConditionAttributes($html);
        }

        $document = $this->loadDocument($html);

        foreach ($this->conditionElements($document) as $element) {
            $definition = $this->parseDefinition($element->getAttribute('data-voodbuilder-conditions'));

            if ($definition === null || ! $this->evaluator->passes($definition, $page)) {
                $element->parentNode?->removeChild($element);
            } else {
                $element->removeAttribute('data-voodbuilder-conditions');
            }
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    protected function stripConditionAttributes(string $html): string
    {
        $document = $this->loadDocument($html);

        foreach ($this->conditionElements($document) as $element) {
            $element->removeAttribute('data-voodbuilder-conditions');
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
    protected function conditionElements(DOMDocument $document): array
    {
        $elements = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-conditions')) {
                $elements[] = $element;
            }
        }

        return array_reverse($elements);
    }

    /**
     * @return array{sets?: list<array{conditions?: list<array<string, mixed>>}>}|null
     */
    protected function parseDefinition(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return $html;
    }
}
