<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class EditorBlockRegistry
{
    /** @var list<EditorBlockDefinition> */
    private array $blocks = [];

    public function register(EditorBlockDefinition $block): self
    {
        $this->blocks[$block->id] = $block;

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toEditorBlocks(): array
    {
        return array_values(array_map(
            static fn (EditorBlockDefinition $block): array => $block->toEditorArray(),
            $this->blocks,
        ));
    }
}
