<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use DOMDocument;
use DOMElement;
use Livewire\Component;
use Livewire\Livewire;
use Voodflow\Voodbuilder\Contracts\EditorLivewireBlock;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRenderer;
use Voodflow\Voodbuilder\Support\Editor\EditorLivewireBlockRenderer;
use Voodflow\Voodbuilder\Support\Editor\EditorRichContentBlockAdapter;
use Voodflow\Voodbuilder\Support\Editor\EditorServerBlockRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorLivewireBlockRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Livewire::component('voodbuilder-test-counter', CounterLivewireTestComponent::class);
    }

    public function test_canvas_preview_keeps_the_wrapper_and_never_mounts_livewire(): void
    {
        $html = $this->renderBlock(canvasPreview: true);

        $this->assertStringContainsString('data-voodbuilder-block', $html);
        $this->assertStringContainsString('Livewire preview', $html);
        $this->assertStringNotContainsString('wire:id', $html);
        $this->assertStringNotContainsString('wire:snapshot', $html);
    }

    public function test_published_render_mounts_the_livewire_component(): void
    {
        $html = $this->renderBlock();

        $this->assertStringContainsString('wire:id', $html);
        $this->assertStringContainsString('wire:snapshot', $html);
        $this->assertSame('voodbuilder-test-counter', $this->snapshotComponentName($html));
        // Block identity survives so the published node still says what it is.
        $this->assertStringContainsString('data-voodbuilder-block="test_livewire_counter"', $html);
    }

    public function test_published_mount_keeps_template_classes_and_appends_author_root_utilities(): void
    {
        $html = $this->publishedPoisonedRender();
        $classes = $this->rootClasses($html);

        // Blade template classes stay, author root utilities ride along.
        $this->assertContains('voodbuilder-livewire-counter', $classes);
        $this->assertContains('rounded-lg', $classes);
        $this->assertContains('bg-vp-bg-elv', $classes);
        $this->assertContains('lg:my-20', $classes);

        // Editor plumbing is never published: it would make the publish healer wrap the mount.
        $this->assertNotContains('voodbuilder-editor-section', $classes);
        $this->assertNotContains('voodbuilder-editor-dynamic', $classes);
        $this->assertNotContains('voodbuilder-editor-container', $classes);
    }

    public function test_published_mount_keeps_author_root_identity_and_chrome_attributes(): void
    {
        $html = $this->publishedPoisonedRender();

        $this->assertStringContainsString('id="ifmoo4"', $html);
        $this->assertStringContainsString('data-voodbuilder-block="test_livewire_counter"', $html);
        $this->assertStringContainsString('data-voodbuilder-content-width="normal"', $html);
        $this->assertStringContainsString('data-vb-style-bg-src="/storage/hero.jpg"', $html);
        $this->assertStringContainsString('data-vb-style-bg-opacity="0.55"', $html);
    }

    public function test_published_mount_is_never_wrapped_by_the_blocks_own_layout_node(): void
    {
        $html = $this->publishedPoisonedRender();

        /*
         * A pre-1.11.76 save stamped the shell markers on the block's inner preview
         * row, so the publish wrapper used to copy `flex … max-w-[80rem]` around the
         * whole mount (the shrink the author saw) and drop the block identity.
         */
        $this->assertStringNotContainsString('data-voodbuilder-role="content"', $html);
        $this->assertStringNotContainsString('voodbuilder-editor-container', $html);
        $this->assertStringNotContainsString('max-w-[80rem]', $html);
        $this->assertStringNotContainsString('sm:justify-between', $html);

        // The mount is the only top-level node: no wrapper around it.
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $body = $document->getElementsByTagName('body')->item(0);

        $this->assertNotNull($body);
        $this->assertSame(1, $body->childNodes->length);
        $this->assertSame('section', strtolower($body->firstChild->nodeName));
        $this->assertStringContainsString('wire:snapshot', $body->firstChild->ownerDocument->saveHTML($body->firstChild));
    }

    public function test_published_mount_is_rebuilt_inside_the_author_content_width_shell(): void
    {
        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $this->registry());
        $html = $renderer->render($this->wrappedWithContentWidthShell());

        $this->assertStringContainsString('data-voodbuilder-content-width="custom"', $html);
        $this->assertStringContainsString('max-w-[48rem]', $html);
        $this->assertStringContainsString('data-voodbuilder-role="content"', $html);

        // The mount must sit *inside* the shell, not next to it.
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $shell = $document->getElementsByTagName('div')->item(0);

        $this->assertNotNull($shell);
        $this->assertSame('custom', $shell->getAttribute('data-voodbuilder-content-width'));
        $this->assertStringContainsString('wire:snapshot', $document->saveHTML($shell));
    }

    public function test_published_mount_is_not_wrapped_when_the_author_never_set_a_width(): void
    {
        $html = $this->renderBlock();

        $this->assertStringNotContainsString('voodbuilder-editor-container', $html);
        $this->assertStringNotContainsString('data-voodbuilder-role="content"', $html);
    }

    public function test_published_mount_carries_the_author_measure_stamped_on_the_block_root(): void
    {
        // 1.11.79: a dynamic block without a genuine content shell owns the measure
        // on its root. The published mount must honour it and stay structurally
        // untouched — no wrapper, layout row intact.
        $saved = '<section data-voodbuilder-block="test_livewire_counter"'
            . ' data-voodbuilder-config=\'{"start":1,"secret":"never-forwarded"}\''
            . ' data-voodbuilder-content-width="normal"'
            . ' class="rounded-lg bg-vp-bg-elv p-6 voodbuilder-editor-section voodbuilder-editor-dynamic mx-auto w-full max-w-[80rem]">'
            . '<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">'
            . '<p>Livewire preview</p></div></section>';

        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $this->registry());
        $html = $renderer->render($saved);
        $classes = $this->rootClasses($html);

        $this->assertContains('mx-auto', $classes);
        $this->assertContains('w-full', $classes);
        $this->assertContains('max-w-[80rem]', $classes);
        $this->assertStringContainsString('data-voodbuilder-content-width="normal"', $html);
        $this->assertStringContainsString('data-voodbuilder-block="test_livewire_counter"', $html);
        // No author wrapper is synthesized around the mount.
        $this->assertStringNotContainsString('data-voodbuilder-role="content"', $html);
        $this->assertStringNotContainsString('voodbuilder-editor-container', $html);
    }

    public function test_props_from_config_is_an_allowlist(): void
    {
        $props = CounterLivewireTestBlock::propsFromConfig(['start' => 7, 'secret' => 'leaked']);

        $this->assertSame(['count' => 7], $props);

        $html = $this->renderBlock();

        $this->assertStringContainsString('data-count="1"', $html);
        $this->assertStringNotContainsString('leaked', $html);
    }

    public function test_two_instances_get_distinct_component_ids(): void
    {
        $registry = $this->registry();
        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $registry);

        $html = $renderer->render(
            $this->wrapped('first') . $this->wrapped('second'),
        );

        preg_match_all('/wire:id="([^"]+)"/', $html, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertNotSame($matches[1][0], $matches[1][1]);
    }

    public function test_missing_component_falls_back_to_the_static_preview(): void
    {
        $registry = new EditorServerBlockRegistry;
        $registry->register('Test', MissingComponentLivewireTestBlock::class);

        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $registry);
        $html = $renderer->render($this->wrapped(blockClass: MissingComponentLivewireTestBlock::class));

        $this->assertStringContainsString('Livewire preview', $html);
        $this->assertStringNotContainsString('wire:snapshot', $html);
    }

    private function renderBlock(bool $canvasPreview = false): string
    {
        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $this->registry());

        return $renderer->render($this->wrapped(), null, $canvasPreview);
    }

    private function registry(): EditorServerBlockRegistry
    {
        $registry = new EditorServerBlockRegistry;
        $registry->register('Test', CounterLivewireTestBlock::class);

        return $registry;
    }

    /**
     * @param  class-string<EditorLivewireBlock>  $blockClass
     */
    private function wrapped(string $id = '', string $blockClass = CounterLivewireTestBlock::class): string
    {
        $html = EditorRichContentBlockAdapter::wrap(
            $blockClass::getId(),
            $blockClass::defaultConfig(),
            '<p>placeholder</p>',
        );

        if ($id === '') {
            return $html;
        }

        // Grapes stamps an id on every dropped node; mimic it so the key path runs.
        return (string) preg_replace('/^<(\w+)/', '<$1 id="' . $id . '"', $html, 1);
    }

    /**
     * Same saved markup, but wrapped in the content-width shell the width toolbar writes.
     */
    private function wrappedWithContentWidthShell(): string
    {
        $saved = '<section data-voodbuilder-block="test_livewire_counter"'
            . ' data-voodbuilder-config=\'{"start":1,"secret":"never-forwarded"}\''
            . ' class="voodbuilder-editor-section voodbuilder-editor-dynamic">'
            . '<div data-voodbuilder-role="content" data-voodbuilder-content-width="custom"'
            . ' class="voodbuilder-editor-container w-full mx-auto max-w-[48rem]">'
            . '<p>Livewire preview</p>'
            . '</div></section>';

        return $saved;
    }

    /**
     * The author node a pre-1.11.76 save produced for a Livewire block: the width
     * toolbar stamped the block's own preview layout row as the content shell.
     */
    private function poisonedWithLayoutShell(): string
    {
        return '<section id="ifmoo4" data-voodbuilder-block="test_livewire_counter"'
            . ' data-voodbuilder-config=\'{"start":1,"secret":"never-forwarded"}\''
            . ' data-voodbuilder-content-width="normal"'
            . ' data-vb-style-bg-src="/storage/hero.jpg" data-vb-style-bg-opacity="0.55"'
            . ' class="rounded-lg bg-vp-bg-elv p-6 voodbuilder-editor-section voodbuilder-editor-dynamic lg:my-20">'
            . '<div data-voodbuilder-role="content" data-voodbuilder-content-width="normal"'
            . ' class="voodbuilder-editor-container flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mx-auto w-full max-w-[80rem]">'
            . '<p>Livewire preview</p></div></section>';
    }

    private function publishedPoisonedRender(): string
    {
        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $this->registry());

        return $renderer->render($this->poisonedWithLayoutShell());
    }

    /**
     * @return list<string>
     */
    private function rootClasses(string $html): array
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $body = $document->getElementsByTagName('body')->item(0);

        $this->assertNotNull($body);
        $this->assertTrue($body->firstChild instanceof DOMElement);

        return preg_split('/\s+/', trim($body->firstChild->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function snapshotComponentName(string $html): ?string
    {
        $snapshot = $this->snapshot($html);

        return $snapshot['memo']['name'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(string $html): array
    {
        if (preg_match('/wire:snapshot=(["\'])(.*?)\1/s', $html, $matches) !== 1) {
            $this->fail('No wire:snapshot attribute found in rendered HTML.');
        }

        return (array) json_decode(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5), true);
    }
}

final class CounterLivewireTestBlock implements EditorLivewireBlock
{
    public static function getId(): string
    {
        return 'test_livewire_counter';
    }

    public static function getLabel(): string
    {
        return 'Livewire counter';
    }

    public static function defaultConfig(): array
    {
        return ['start' => 1, 'secret' => 'never-forwarded'];
    }

    public static function component(): string
    {
        return 'voodbuilder-test-counter';
    }

    public static function propsFromConfig(array $config): array
    {
        return ['count' => (int) ($config['start'] ?? 0)];
    }

    public static function toHtml(array $config, array $context): string
    {
        return EditorLivewireBlockRenderer::mount(self::class, $config);
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return '<section class="rounded-lg bg-vp-bg-elv p-6"><p class="text-sm text-vp-text-2">Livewire preview</p></section>';
    }
}

final class MissingComponentLivewireTestBlock implements EditorLivewireBlock
{
    public static function getId(): string
    {
        return 'test_livewire_missing';
    }

    public static function getLabel(): string
    {
        return 'Livewire missing';
    }

    public static function defaultConfig(): array
    {
        return [];
    }

    public static function component(): string
    {
        return 'voodbuilder-test-does-not-exist';
    }

    public static function propsFromConfig(array $config): array
    {
        return [];
    }

    public static function toHtml(array $config, array $context): string
    {
        return EditorLivewireBlockRenderer::mount(self::class, $config);
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return '<section class="p-6"><p>Livewire preview</p></section>';
    }
}

class CounterLivewireTestComponent extends Component
{
    public int $count = 0;

    public function render(): string
    {
        return '<section class="voodbuilder-livewire-counter rounded-lg p-6"><span data-count="' . $this->count . '">{{ $count }}</span></section>';
    }
}
