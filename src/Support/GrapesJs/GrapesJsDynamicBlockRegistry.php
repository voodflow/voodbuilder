<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

final class GrapesJsDynamicBlockRegistry
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

    public function registerEditorBlocks(GrapesJsBlockRegistry $registry, ?int $eventId = null): void
    {
        foreach ($this->blocks as $blockId => $blockClass) {
            $category = $this->categories[$blockId] ?? 'Dynamic';

            $registry->register(
                GrapesJsRichContentBlockAdapter::toDefinition($blockClass, $category, $eventId),
            );
        }
    }
}
