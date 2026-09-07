<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

/**
 * Editor Dynamic Block Registry.
 */
final class EditorDynamicBlockRegistry
{
    /** @var array<string, class-string<RichContentCustomBlock>> */
    protected array $blocks = [];

    /** @var array<string, string> */
    protected array $categories = [];

    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public function register(string $category, string $blockClass): self
    {
        $this->blocks[$blockClass::getId()] = $blockClass;
        $this->categories[$blockClass::getId()] = $category;

        return $this;
    }

    /**
     * @return array<string, class-string<RichContentCustomBlock>>
     */
    public function blocks(): array
    {
        return $this->blocks;
    }

    public function categoryFor(string $blockId): ?string
    {
        return $this->categories[$blockId] ?? null;
    }

    /**
     * @return class-string<RichContentCustomBlock>|null
     */
    public function resolve(string $blockId): ?string
    {
        return $this->blocks[$blockId] ?? null;
    }

    public function registerEditorBlocks(EditorBlockRegistry $registry, ?int $eventId = null): void
    {
        foreach ($this->blocks as $blockId => $blockClass) {
            if (self::isExcludedFromEditor($blockId)) {
                continue;
            }

            $category = $this->categories[$blockId] ?? 'Dynamic';

            try {
                $registry->register(
                    EditorRichContentBlockAdapter::toDefinition($blockClass, $category, $eventId),
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected static function excludedEditorBlocks(): array
    {
        return array_values(array_filter(
            (array) config('voodbuilder.editor.excluded_editor_blocks', []),
            static fn (mixed $blockId): bool => is_string($blockId) && $blockId !== '',
        ));
    }

    protected static function isExcludedFromEditor(string $blockId): bool
    {
        return in_array($blockId, self::excludedEditorBlocks(), true);
    }
}
