<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

final class GrapesJsBlockRegistry
{
    /** @var list<GrapesJsBlockDefinition> */
    private array $blocks = [];

    public function register(GrapesJsBlockDefinition $block): self
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
            static fn (GrapesJsBlockDefinition $block): array => $block->toEditorArray(),
            $this->blocks,
        ));
    }
}
