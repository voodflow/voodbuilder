<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMDocument;
use DOMElement;

/**
 * Editor Imported Tailwind Support.
 */
final class EditorImportedTailwindSupport
{
    /**
     * @var array<string, string>
     */
    private const CLASS_REPLACEMENTS = [
        'bg-linear-to-t' => 'bg-gradient-to-t',
        'bg-linear-to-tr' => 'bg-gradient-to-tr',
        'bg-linear-to-r' => 'bg-gradient-to-r',
        'bg-linear-to-br' => 'bg-gradient-to-br',
        'bg-linear-to-b' => 'bg-gradient-to-b',
        'bg-linear-to-bl' => 'bg-gradient-to-bl',
        'bg-linear-to-l' => 'bg-gradient-to-l',
        'bg-linear-to-tl' => 'bg-gradient-to-tl',
        'shadow-xs' => 'shadow-sm',
    ];

    /**
     * @var array<string, string>
     */
    private const LINE_HEIGHT_REPLACEMENTS = [
        'text-sm/6' => 'text-sm leading-6',
        'text-base/7' => 'text-base leading-7',
        'text-xl/8' => 'text-xl leading-8',
        'sm:text-xl/8' => 'sm:text-xl sm:leading-8',
    ];

    public static function prepareHtml(string $html): string
    {
        $html = self::migrateClassTokens($html);
        $html = self::inlineBackgroundImageClasses($html);
        $html = self::simplifyCustomElements($html);
        $html = self::stripNonStandardAttributes($html);
        $html = self::markPastedComponentRoot($html);
        $html = self::ensureEditorLayoutShell($html);

        return self::ensureDarkVariantScope(self::bakeSvgPaintInHtml(self::stripSpuriousSvgBakedPaint($html)));
    }

    public static function stripSpuriousSvgBakedPaint(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<svg')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $svgNodes = $document->getElementsByTagName('svg');

        if ($svgNodes->length === 0) {
            return $html;
        }

        foreach ($svgNodes as $svg) {
            if (! $svg instanceof DOMElement) {
                continue;
            }

            if (! self::svgPreservesTailwindCurrentColorPaint($svg)) {
                continue;
            }

            $style = self::parseStyleAttribute($svg->getAttribute('style'));
            $bakedPaint = $style['color'] ?? $style['fill'] ?? $style['stroke'] ?? null;

            if ($bakedPaint === null || ! self::isGenericBlackPaint($bakedPaint)) {
                continue;
            }

            foreach (['color', 'fill', 'stroke'] as $property) {
                unset($style[$property]);
            }

            if ($style === []) {
                $svg->removeAttribute('style');
            } else {
                $svg->setAttribute('style', self::serializeStyleAttribute($style));
            }

            foreach ($svg->getElementsByTagName('*') as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                if (self::isGenericBlackPaint($node->getAttribute('fill'))) {
                    $node->setAttribute(
                        'fill',
                        self::svgRootFillIsNone($svg) ? 'none' : 'currentColor',
                    );
                }

                if (self::isGenericBlackPaint($node->getAttribute('stroke'))) {
                    $node->setAttribute('stroke', 'currentColor');
                }
            }
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    protected static function svgPreservesTailwindCurrentColorPaint(DOMElement $svg): bool
    {
        if (self::svgIsStrokeOnlyCurrentColorIcon($svg)) {
            return true;
        }

        $class = $svg->getAttribute('class');

        if ($class === '' || ! preg_match('/\btext-(?:vp-|gray-|white|black|primary|foreground|indigo-)/', $class)) {
            return false;
        }

        $rootFill = strtolower($svg->getAttribute('fill'));

        if ($rootFill === 'currentcolor') {
            return true;
        }

        foreach ($svg->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $fill = strtolower($node->getAttribute('fill'));
            $stroke = strtolower($node->getAttribute('stroke'));

            if ($fill === 'currentcolor' || $stroke === 'currentcolor') {
                return true;
            }
        }

        return false;
    }

    protected static function svgIsStrokeOnlyCurrentColorIcon(DOMElement $svg): bool
    {
        if (! self::svgRootFillIsNone($svg)) {
            return false;
        }

        if (self::svgHasUsableStroke($svg)) {
            return true;
        }

        // Stroke-only watermarks / icons: fill="none" and no filled shapes.
        foreach ($svg->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $fill = strtolower(trim($node->getAttribute('fill')));

            if ($fill !== '' && $fill !== 'none' && ! str_starts_with($fill, 'url(')) {
                return false;
            }
        }

        return self::svgRootFillIsNone($svg);
    }

    protected static function svgHasUsableStroke(DOMElement $svg): bool
    {
        $rootStroke = strtolower(trim($svg->getAttribute('stroke')));

        if ($rootStroke !== '' && $rootStroke !== 'none') {
            return true;
        }

        $styleStroke = strtolower(trim(self::parseStyleAttribute($svg->getAttribute('style'))['stroke'] ?? ''));

        if ($styleStroke !== '' && $styleStroke !== 'none') {
            return true;
        }

        foreach ($svg->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $stroke = strtolower(trim($node->getAttribute('stroke')));

            if ($stroke !== '' && $stroke !== 'none') {
                return true;
            }
        }

        return false;
    }

    protected static function svgRootFillIsNone(DOMElement $svg): bool
    {
        $fill = strtolower(trim($svg->getAttribute('fill')));

        if ($fill === 'none') {
            return true;
        }

        $styleFill = strtolower(trim(self::parseStyleAttribute($svg->getAttribute('style'))['fill'] ?? ''));

        return $styleFill === 'none';
    }

    protected static function restoreStrokeOnlySvgCurrentColorPaint(DOMElement $svg): void
    {
        $style = self::parseStyleAttribute($svg->getAttribute('style'));

        foreach (['color', 'fill', 'stroke'] as $property) {
            unset($style[$property]);
        }

        if ($style === []) {
            $svg->removeAttribute('style');
        } else {
            $svg->setAttribute('style', self::serializeStyleAttribute($style));
        }

        $svg->setAttribute('fill', 'none');

        foreach ($svg->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $fill = strtolower(trim($node->getAttribute('fill')));

            if ($fill === '' || self::isGenericBlackPaint($node->getAttribute('fill')) || $fill === 'currentcolor') {
                $node->setAttribute('fill', 'none');
            }

            if (self::isGenericBlackPaint($node->getAttribute('stroke'))) {
                $node->setAttribute('stroke', 'currentColor');
            }
        }
    }

    protected static function isGenericBlackPaint(string $paint): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s*!important\s*$/i', '', $paint) ?? $paint));

        return in_array($normalized, ['#000', '#000000', 'black', 'rgb(0, 0, 0)', 'rgb(0,0,0)'], true);
    }

    protected static function serializeStyleAttribute(array $style): string
    {
        $chunks = [];

        foreach ($style as $property => $value) {
            if ($property === '' || $value === '') {
                continue;
            }

            $chunks[] = "{$property}: {$value}";
        }

        return implode('; ', $chunks);
    }

    public static function bakeSvgPaintInHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<svg')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $svgNodes = $document->getElementsByTagName('svg');

        if ($svgNodes->length === 0) {
            return $html;
        }

        foreach ($svgNodes as $svg) {
            if (! $svg instanceof DOMElement) {
                continue;
            }

            if (self::svgPreservesTailwindCurrentColorPaint($svg)) {
                $style = self::parseStyleAttribute($svg->getAttribute('style'));
                $bakedPaint = $style['color'] ?? $style['fill'] ?? $style['stroke'] ?? null;

                if ($bakedPaint !== null && self::isGenericBlackPaint($bakedPaint)) {
                    self::restoreStrokeOnlySvgCurrentColorPaint($svg);

                    continue;
                }
            }

            $paint = self::resolveSvgPaintFromElement($svg);

            if ($paint === null) {
                continue;
            }

            self::applySvgPaintToTree($svg, $paint);
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    public static function resolveSvgPaintFromElement(DOMElement $svg): ?string
    {
        $style = self::parseStyleAttribute($svg->getAttribute('style'));

        if (isset($style['color']) && $style['color'] !== '') {
            return $style['color'];
        }

        if (isset($style['fill']) && $style['fill'] !== '' && strtolower($style['fill']) !== 'none') {
            return $style['fill'];
        }

        if (isset($style['stroke']) && $style['stroke'] !== '' && strtolower($style['stroke']) !== 'none') {
            return $style['stroke'];
        }

        foreach ($svg->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $fill = strtolower($node->getAttribute('fill'));

            if ($fill !== '' && $fill !== 'currentcolor' && $fill !== 'none') {
                return $node->getAttribute('fill');
            }

            $stroke = strtolower($node->getAttribute('stroke'));

            if ($stroke !== '' && $stroke !== 'currentcolor' && $stroke !== 'none') {
                return $node->getAttribute('stroke');
            }
        }

        return null;
    }

    protected static function applySvgPaintToTree(DOMElement $svg, string $paint): void
    {
        // Stroke-only watermarks/icons must never receive a solid fill — even when
        // the resolved paint is a brand color (violet, cyan, …).
        if (self::svgIsStrokeOnlyCurrentColorIcon($svg)) {
            self::restoreStrokeOnlySvgCurrentColorPaint($svg);

            $existingStyle = self::parseStyleAttribute($svg->getAttribute('style'));
            $style = self::mergeStyleProperty($svg->getAttribute('style'), 'color', $paint);
            $style = self::mergeStyleProperty($style, 'stroke', $paint);
            // Explicitly keep fill transparent in the style bag so Grapes/CSS cannot
            // override the presentation attribute with a solid paint.
            $style = self::mergeStyleProperty($style, 'fill', 'none');

            foreach (['stroke-width', 'opacity'] as $property) {
                if (isset($existingStyle[$property]) && $existingStyle[$property] !== '') {
                    $style = self::mergeStyleProperty($style, $property, $existingStyle[$property]);
                }
            }

            $svg->setAttribute('fill', 'none');
            $svg->setAttribute('style', $style);

            foreach ($svg->getElementsByTagName('*') as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $fill = strtolower(trim($node->getAttribute('fill')));

                if ($fill === '' || $fill === 'currentcolor' || self::isGenericBlackPaint($node->getAttribute('fill'))) {
                    $node->setAttribute('fill', 'none');
                }

                $stroke = strtolower(trim($node->getAttribute('stroke')));

                if (self::isPaintableSvgStrokeValue($stroke) || $stroke === 'currentcolor') {
                    $node->setAttribute('stroke', $paint);
                }
            }

            return;
        }

        $existingStyle = self::parseStyleAttribute($svg->getAttribute('style'));
        $style = self::mergeStyleProperty($svg->getAttribute('style'), 'color', $paint);

        if (! self::svgRootFillIsNone($svg)) {
            $style = self::mergeStyleProperty($style, 'fill', $paint);
        }

        $style = self::mergeStyleProperty($style, 'stroke', $paint);

        foreach (['stroke-width', 'opacity'] as $property) {
            if (isset($existingStyle[$property]) && $existingStyle[$property] !== '') {
                $style = self::mergeStyleProperty($style, $property, $existingStyle[$property]);
            }
        }

        $svg->setAttribute('style', $style);

        foreach ($svg->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $fill = strtolower($node->getAttribute('fill'));

            if (self::isPaintableSvgFillValue($fill, self::svgRootFillIsNone($svg))) {
                $node->setAttribute('fill', $paint);
            }

            $stroke = strtolower($node->getAttribute('stroke'));

            if (self::isPaintableSvgStrokeValue($stroke)) {
                $node->setAttribute('stroke', $paint);
            }
        }
    }

    protected static function isPaintableSvgFillValue(string $fill, bool $rootFillIsNone = false): bool
    {
        $fill = strtolower(trim($fill));

        if ($fill === 'none' || str_starts_with($fill, 'url(')) {
            return false;
        }

        if ($rootFillIsNone && ($fill === '' || $fill === 'currentcolor')) {
            return false;
        }

        return true;
    }

    protected static function isPaintableSvgStrokeValue(string $stroke): bool
    {
        $stroke = strtolower(trim($stroke));

        if ($stroke === '' || $stroke === 'none' || str_starts_with($stroke, 'url(')) {
            return false;
        }

        return true;
    }

    protected static function parseStyleAttribute(string $style): array
    {
        if ($style === '') {
            return [];
        }

        $parsed = [];

        foreach (explode(';', $style) as $chunk) {
            $chunk = trim($chunk);

            if ($chunk === '' || ! str_contains($chunk, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $chunk, 2));
            $property = strtolower($property);
            $value = preg_replace('/\s*!important\s*$/i', '', $value) ?? $value;

            if ($property !== '' && $value !== '') {
                $parsed[$property] = $value;
            }
        }

        return $parsed;
    }

    public static function ensureDarkVariantScope(string $html): string
    {
        if ($html === '' || ! preg_match('/\bdark:[A-Za-z0-9_\-!\/\[\]#%.]+/', $html)) {
            return $html;
        }

        return preg_replace_callback(
            '/\bclass=(["\'])([^"\']*\bvoodbuilder-pasted-component\b[^"\']*)\1/i',
            static function (array $matches): string {
                $classes = preg_split('/\s+/', trim($matches[2])) ?: [];

                if (in_array('dark', $classes, true)) {
                    return $matches[0];
                }

                return 'class='.$matches[1].'dark '.$matches[2].$matches[1];
            },
            $html,
            1,
        ) ?? $html;
    }

    /**
     * @return list<string>
     */
    public static function extractClassNames(string $html): array
    {
        if (! preg_match_all('/\bclass=(["\'])(.*?)\1/is', $html, $matches)) {
            return [];
        }

        $classes = [];

        foreach ($matches[2] as $classAttribute) {
            foreach (preg_split('/\s+/', trim(html_entity_decode($classAttribute, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?: [] as $class) {
                $class = trim($class);

                if ($class !== '') {
                    $classes[$class] = true;
                }
            }
        }

        return array_keys($classes);
    }

    public static function migrateClassTokens(string $html): string
    {
        foreach (self::LINE_HEIGHT_REPLACEMENTS as $from => $to) {
            $html = preg_replace(
                '/\b'.preg_quote($from, '/').'\b/',
                $to,
                $html,
            ) ?? $html;
        }

        return preg_replace_callback(
            '/\bclass=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $classes = preg_split('/\s+/', trim($matches[2])) ?: [];
                $migrated = [];

                foreach ($classes as $class) {
                    $class = trim($class);

                    if ($class === '') {
                        continue;
                    }

                    $migrated[] = self::CLASS_REPLACEMENTS[$class] ?? $class;
                }

                return 'class='.$quote.implode(' ', $migrated).$quote;
            },
            $html,
        ) ?? $html;
    }

    /**
     * Framework / JSX layout tags often appear in Tailwind marketing snippets
     * (Astro <Container>, etc.). Map them to plain divs so Grapes + toolbar work.
     *
     * @var list<string>
     */
    private const LAYOUT_PSEUDO_TAGS = [
        'container',
        'row',
        'col',
        'column',
        'columns',
        'fragment',
        'el-dialog',
        'el-dialog-panel',
    ];

    public static function simplifyCustomElements(string $html): string
    {
        $needsPass = str_contains($html, 'el-dialog');

        foreach (self::LAYOUT_PSEUDO_TAGS as $tag) {
            if (stripos($html, '<'.$tag) !== false || stripos($html, '</'.$tag) !== false) {
                $needsPass = true;
                break;
            }
        }

        if (! $needsPass) {
            return $html;
        }

        $document = self::loadDocument($html);

        foreach (self::LAYOUT_PSEUDO_TAGS as $tag) {
            while (true) {
                $elements = $document->getElementsByTagName($tag);

                if ($elements->length === 0) {
                    break;
                }

                $element = $elements->item(0);

                if (! $element instanceof DOMElement) {
                    break;
                }

                $replacement = $document->createElement('div');

                if ($element->hasAttributes()) {
                    foreach ($element->attributes as $attribute) {
                        if (in_array($attribute->name, ['command', 'commandfor'], true)) {
                            continue;
                        }

                        $replacement->setAttribute($attribute->name, $attribute->value);
                    }
                }

                if (strtolower($tag) === 'container') {
                    $classes = trim($replacement->getAttribute('class'));
                    $tokens = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                    if (! in_array('voodbuilder-editor-container', $tokens, true)) {
                        $tokens[] = 'voodbuilder-editor-container';
                    }

                    if (! in_array('w-full', $tokens, true)) {
                        $tokens[] = 'w-full';
                    }

                    $replacement->setAttribute('class', implode(' ', $tokens));

                    if (! $replacement->hasAttribute('data-voodbuilder-role')) {
                        $replacement->setAttribute('data-voodbuilder-role', 'content');
                    }
                }

                while ($element->firstChild !== null) {
                    $replacement->appendChild($element->firstChild);
                }

                $element->parentNode?->replaceChild($replacement, $element);
            }
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    /**
     * Ensure imported snippets expose the standard section → content shell so the
     * canvas content-width ("resize") toolbar and Layers stay compliant.
     */
    public static function ensureEditorLayoutShell(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return $html;
        }

        $document = self::loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        $roots = [];

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $roots[] = $child;
            }
        }

        if ($roots === []) {
            return $html;
        }

        if (count($roots) === 1 && self::isEditorSectionElement($roots[0])) {
            self::ensureContentWrapperOnSection($document, $roots[0]);
            self::promotePastedComponentClassToSection($roots[0]);

            return self::extractBodyHtml($document) ?? $html;
        }

        // Single root that is already a content shell: wrap with section only.
        if (count($roots) === 1 && self::isContentShellElement($roots[0])) {
            $section = self::createEditorSection($document);
            $container = $roots[0];
            self::stripPastedComponentClass($container);
            self::stampContentShellAttributes($container);
            $section->appendChild($container);
            self::replaceBodyChildren($body, [$section]);

            return self::extractBodyHtml($document) ?? $html;
        }

        $section = self::createEditorSection($document);

        if (count($roots) === 1) {
            $only = $roots[0];
            $directContentShell = self::findDirectContentShellChild($only);

            if ($directContentShell !== null) {
                // e.g. #blog > .voodbuilder-editor-container — promote shell, keep outer as body.
                self::stripPastedComponentClass($only);
                $id = trim($only->getAttribute('id'));

                if ($id !== '' && ! $section->hasAttribute('id')) {
                    $section->setAttribute('id', $id);
                    $only->removeAttribute('id');
                }

                while ($only->firstChild !== null) {
                    $section->appendChild($only->firstChild);
                }

                self::ensureContentWrapperOnSection($document, $section);
                self::replaceBodyChildren($body, [$section]);

                return self::extractBodyHtml($document) ?? $html;
            }
        }

        $container = self::createEditorContentContainer($document);

        foreach ($roots as $root) {
            self::stripPastedComponentClass($root);

            if (count($roots) === 1) {
                $id = trim($root->getAttribute('id'));

                if ($id !== '' && ! $section->hasAttribute('id')) {
                    $section->setAttribute('id', $id);
                    $root->removeAttribute('id');
                }

                // Unwrap a trivial outer div (only id/class leftovers).
                if (self::isTrivialWrapper($root)) {
                    while ($root->firstChild !== null) {
                        $container->appendChild($root->firstChild);
                    }

                    continue;
                }
            }

            $container->appendChild($root);
        }

        $section->appendChild($container);
        self::replaceBodyChildren($body, [$section]);

        return self::extractBodyHtml($document) ?? $html;
    }

    protected static function createEditorSection(DOMDocument $document): DOMElement
    {
        $section = $document->createElement('section');
        $section->setAttribute('class', 'voodbuilder-editor-section voodbuilder-pasted-component relative w-full');

        return $section;
    }

    protected static function createEditorContentContainer(DOMDocument $document): DOMElement
    {
        $container = $document->createElement('div');
        self::stampContentShellAttributes($container);

        return $container;
    }

    protected static function stampContentShellAttributes(DOMElement $element): void
    {
        $classes = preg_split('/\s+/', trim($element->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach (['voodbuilder-editor-container', 'mx-auto', 'w-full', 'max-w-[80rem]'] as $token) {
            if (! in_array($token, $classes, true)) {
                $classes[] = $token;
            }
        }

        $element->setAttribute('class', implode(' ', $classes));
        $element->setAttribute('data-voodbuilder-role', 'content');

        if (! $element->hasAttribute('data-voodbuilder-content-width')) {
            $element->setAttribute('data-voodbuilder-content-width', 'normal');
        }

        $style = trim($element->getAttribute('style'));
        $required = 'width:100%;max-width:80rem;margin-left:auto;margin-right:auto;';

        if ($style === '') {
            $element->setAttribute('style', $required);
        } elseif (! str_contains($style, 'max-width')) {
            $element->setAttribute('style', rtrim($style, ';').';'.$required);
        }
    }

    protected static function isEditorSectionElement(DOMElement $element): bool
    {
        if (strtolower($element->tagName) !== 'section') {
            return false;
        }

        $classes = preg_split('/\s+/', trim($element->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return in_array('voodbuilder-editor-section', $classes, true)
            || $element->hasAttribute('data-voodbuilder-section-block')
            || $element->hasAttribute('data-voodbuilder-layout');
    }

    protected static function isContentShellElement(DOMElement $element): bool
    {
        $classes = preg_split('/\s+/', trim($element->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return in_array('voodbuilder-editor-container', $classes, true)
            || $element->getAttribute('data-voodbuilder-role') === 'content';
    }

    protected static function findDirectContentShellChild(DOMElement $parent): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && self::isContentShellElement($child)) {
                return $child;
            }
        }

        return null;
    }

    protected static function ensureContentWrapperOnSection(DOMDocument $document, DOMElement $section): void
    {
        $existing = null;

        foreach ($section->childNodes as $child) {
            if ($child instanceof DOMElement && self::isContentShellElement($child)) {
                $existing = $child;
                break;
            }
        }

        if ($existing instanceof DOMElement) {
            self::stampContentShellAttributes($existing);

            return;
        }

        $container = self::createEditorContentContainer($document);
        $move = [];

        foreach ($section->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $move[] = $child;
            }
        }

        foreach ($move as $child) {
            $container->appendChild($child);
        }

        $section->appendChild($container);
    }

    protected static function promotePastedComponentClassToSection(DOMElement $section): void
    {
        $classes = preg_split('/\s+/', trim($section->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (! in_array('voodbuilder-editor-section', $classes, true)) {
            array_unshift($classes, 'voodbuilder-editor-section');
        }

        if (! in_array('voodbuilder-pasted-component', $classes, true)) {
            $classes[] = 'voodbuilder-pasted-component';
        }

        if (! in_array('relative', $classes, true)) {
            $classes[] = 'relative';
        }

        if (! in_array('w-full', $classes, true)) {
            $classes[] = 'w-full';
        }

        $section->setAttribute('class', implode(' ', $classes));

        foreach ($section->childNodes as $child) {
            if ($child instanceof DOMElement) {
                self::stripPastedComponentClass($child);
            }
        }
    }

    protected static function stripPastedComponentClass(DOMElement $element): void
    {
        if (! $element->hasAttribute('class')) {
            return;
        }

        $classes = array_values(array_filter(
            preg_split('/\s+/', trim($element->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            static fn (string $token): bool => $token !== 'voodbuilder-pasted-component',
        ));

        if ($classes === []) {
            $element->removeAttribute('class');
        } else {
            $element->setAttribute('class', implode(' ', $classes));
        }
    }

    protected static function isTrivialWrapper(DOMElement $element): bool
    {
        if (strtolower($element->tagName) !== 'div') {
            return false;
        }

        $classes = preg_split('/\s+/', trim($element->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $meaningful = array_values(array_filter(
            $classes,
            static fn (string $token): bool => ! in_array($token, ['relative', 'voodbuilder-pasted-component'], true),
        ));

        if ($meaningful !== []) {
            return false;
        }

        foreach ($element->attributes as $attribute) {
            if (! in_array($attribute->name, ['id', 'class'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<DOMElement>  $children
     */
    protected static function replaceBodyChildren(DOMElement $body, array $children): void
    {
        while ($body->firstChild !== null) {
            $body->removeChild($body->firstChild);
        }

        foreach ($children as $child) {
            $body->appendChild($child);
        }
    }

    public static function inlineBackgroundImageClasses(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'bg-[url')) {
            return $html;
        }

        $document = self::loadDocument($html);

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('class')) {
                continue;
            }

            $classes = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];
            $remaining = [];
            $backgroundUrl = null;

            foreach ($classes as $class) {
                $class = trim($class);

                if ($class === '') {
                    continue;
                }

                $url = self::parseBackgroundUrlClass($class);

                if ($url !== null) {
                    $backgroundUrl = $url;

                    continue;
                }

                $remaining[] = $class;
            }

            if ($backgroundUrl === null) {
                continue;
            }

            $style = $element->getAttribute('style');
            $style = self::mergeStyleProperty($style, 'background-image', self::cssUrl($backgroundUrl));

            if (in_array('bg-cover', $remaining, true)) {
                $style = self::mergeStyleProperty($style, 'background-size', 'cover');
            }

            if (in_array('bg-center', $remaining, true)) {
                $style = self::mergeStyleProperty($style, 'background-position', 'center');
            }

            if (in_array('bg-no-repeat', $remaining, true)) {
                $style = self::mergeStyleProperty($style, 'background-repeat', 'no-repeat');
            }

            $element->setAttribute('style', $style);
            $element->setAttribute('class', implode(' ', $remaining));
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    public static function parseBackgroundUrlClass(string $class): ?string
    {
        if (! str_starts_with($class, 'bg-[url(') || ! str_ends_with($class, ')]')) {
            return null;
        }

        $inner = substr($class, 8, -2);
        $inner = trim($inner);

        if ($inner === '') {
            return null;
        }

        $quote = $inner[0];

        if (($quote === '"' || $quote === "'") && str_ends_with($inner, $quote)) {
            return substr($inner, 1, -1);
        }

        return $inner;
    }

    public static function stripNonStandardAttributes(string $html): string
    {
        return preg_replace('/\s(?:command|commandfor)=(["\']).*?\1/i', '', $html) ?? $html;
    }

    public static function markPastedComponentRoot(string $html): string
    {
        if ($html === '' || str_contains($html, 'voodbuilder-pasted-component')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null || $body->childNodes->length !== 1) {
            return '<div class="voodbuilder-pasted-component relative">'.$html.'</div>';
        }

        $root = $body->firstChild;

        if (! $root instanceof DOMElement) {
            return '<div class="voodbuilder-pasted-component relative">'.$html.'</div>';
        }

        $existing = trim($root->getAttribute('class'));
        $root->setAttribute('class', trim(($existing !== '' ? $existing.' ' : '').'voodbuilder-pasted-component relative'));

        return self::extractBodyHtml($document) ?? $html;
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

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return trim($html);
    }

    protected static function mergeStyleProperty(string $style, string $property, string $value): string
    {
        $declaration = $property.': '.$value.';';
        $style = trim($style);

        if ($style === '') {
            return $declaration;
        }

        $pattern = '/\b'.preg_quote($property, '/').'\s*:[^;]*;?/i';
        $replaced = preg_replace($pattern, $declaration, $style);

        if (is_string($replaced) && $replaced !== $style) {
            return trim($replaced);
        }

        return rtrim($style, ';').';'.$declaration;
    }

    protected static function cssUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return "url('')";
        }

        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $url);

        return "url('{$escaped}')";
    }
}
