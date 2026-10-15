<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Cache;
use Voodflow\Voodbuilder\Contracts\EditorLivewireBlock;

/**
 * Utilities used by Livewire block markup, merged into the published stylesheet.
 *
 * Page CSS is compiled from the *saved* HTML, and the saved HTML only holds the
 * static preview: a utility that appears exclusively inside the Livewire component
 * view would never be generated and the mounted markup would render unstyled.
 * This compiles the utilities from the same preview markup the canvas renders.
 *
 * Block authors must keep the component view and the preview sharing utilities —
 * ideally the same partial — or the compiled set will not cover the mount.
 *
 * Compiling shells out to Node, so results are cached by markup, not by page.
 */
final class EditorLivewireBlockCss
{
    public function cssForHtml(string $html): ?string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-block')) {
            return null;
        }

        if (! EditorComponentTailwindCompiler::isAvailable()) {
            return null;
        }

        $sheets = [];

        foreach ($this->previewHtmlForLivewireBlocks($html) as $previewHtml) {
            $css = $this->compileOnce($previewHtml);

            if (filled($css)) {
                $sheets[$css] = true;
            }
        }

        return $sheets !== [] ? implode("\n\n", array_keys($sheets)) : null;
    }

    /**
     * @return list<string>
     */
    protected function previewHtmlForLivewireBlocks(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $previews = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-voodbuilder-block')) {
                continue;
            }

            $blockClass = app(EditorServerBlockRegistry::class)
                ->resolve(trim((string) $element->getAttribute('data-voodbuilder-block')));

            if ($blockClass === null || ! is_subclass_of($blockClass, EditorLivewireBlock::class)) {
                continue;
            }

            $config = EditorDynamicBlockAttributeNormalizer::decodeConfig(
                (string) $element->getAttribute('data-voodbuilder-config'),
            );

            $previews[] = $blockClass::toPreviewHtml(
                $config !== [] ? $config : $blockClass::defaultConfig(),
                [],
            );
        }

        return $previews;
    }

    protected function compileOnce(string $previewHtml): ?string
    {
        if (trim($previewHtml) === '') {
            return null;
        }

        return Cache::remember(
            'voodbuilder:livewire-block-css:' . md5($previewHtml),
            now()->addDay(),
            static fn (): ?string => EditorComponentTailwindCompiler::compile($previewHtml, 'page'),
        );
    }
}
