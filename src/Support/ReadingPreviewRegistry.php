<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Companion-owned reading previews for the Layout visual builder Reading tab.
 *
 * Plugins register a sample article (HTML) so the layout editor can preview
 * real channel flavour without Voodbuilder knowing product markup.
 *
 * @phpstan-type PreviewDefinition array{
 *     label: string,
 *     html: string,
 *     eyebrow?: string|null,
 *     secondaryFont?: bool,
 *     hasSidebar?: bool,
 *     hasAside?: bool,
 *     sidebarHtml?: string|null,
 *     asideHtml?: string|null,
 * }
 */
final class ReadingPreviewRegistry
{
    /** @var array<string, PreviewDefinition> */
    private array $previews = [];

    /**
     * @param  PreviewDefinition|callable(): PreviewDefinition  $definition
     */
    public function register(string $channelId, array|callable $definition): void
    {
        $channelId = trim($channelId);

        if ($channelId === '') {
            return;
        }

        $resolved = is_callable($definition) ? $definition() : $definition;

        if (! is_array($resolved) || ! isset($resolved['label'], $resolved['html'])) {
            return;
        }

        $this->previews[$channelId] = [
            'label' => (string) $resolved['label'],
            'html' => (string) $resolved['html'],
            'eyebrow' => isset($resolved['eyebrow']) ? (string) $resolved['eyebrow'] : null,
            'secondaryFont' => (bool) ($resolved['secondaryFont'] ?? $resolved['hasSidebar'] ?? false),
            'hasSidebar' => (bool) ($resolved['hasSidebar'] ?? $resolved['secondaryFont'] ?? false),
            'hasAside' => (bool) ($resolved['hasAside'] ?? $resolved['hasSidebar'] ?? $resolved['secondaryFont'] ?? false),
            'sidebarHtml' => isset($resolved['sidebarHtml']) ? (string) $resolved['sidebarHtml'] : null,
            'asideHtml' => isset($resolved['asideHtml']) ? (string) $resolved['asideHtml'] : null,
        ];
    }

    /**
     * @return array<string, PreviewDefinition>
     */
    public function all(): array
    {
        return $this->previews;
    }

    public function hasCompanions(): bool
    {
        return $this->previews !== [];
    }

    /**
     * @return array<string, string> channel id => label
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->previews as $id => $preview) {
            $options[$id] = $preview['label'];
        }

        if ($options === []) {
            $options['sample'] = __('voodbuilder::chrome_layouts.preview.sample');
        }

        return $options;
    }

    /**
     * @return PreviewDefinition
     */
    public function resolve(string $channelId): array
    {
        if ($channelId !== 'sample' && isset($this->previews[$channelId])) {
            return $this->previews[$channelId];
        }

        return [
            'label' => __('voodbuilder::chrome_layouts.preview.sample'),
            'eyebrow' => __('voodbuilder::chrome_layouts.preview.sample_eyebrow'),
            'html' => self::defaultSampleHtml(),
            'secondaryFont' => false,
            'hasSidebar' => false,
            'hasAside' => false,
            'sidebarHtml' => null,
            'asideHtml' => null,
        ];
    }

    public static function defaultSampleHtml(): string
    {
        return <<<'HTML'
<h1>Layout model</h1>
<p class="lead">Reading typography from this layout flows into companion pages that sit inside the Voodbuilder chrome shell.</p>
<p>Plugins such as documentation or tutorials keep their own markup. They only consume <code>--vp-font-family-doc</code> and <code>--vp-font-size-doc</code>, so changes here update every reading surface that uses this layout.</p>
<h2>Recommended pattern</h2>
<ul>
<li><strong>Section</strong> — outermost divider, full width</li>
<li><strong>Container</strong> — measured content column</li>
<li><strong>Block</strong> — reusable unit of content</li>
</ul>
<pre class="vp-code-block"><code>Section → Container → Block(s) → Content</code></pre>
<blockquote>Third-party plugins register their own preview sample via <code>Voodbuilder::readingPreview()</code>.</blockquote>
HTML;
    }
}
