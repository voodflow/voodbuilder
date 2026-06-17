<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

class RichContentBlockRegistry
{
    /** @var array<string, array<class-string<RichContentCustomBlock>>> */
    protected array $groups = [];

    /** @var list<class-string<RichContentCustomBlock>> */
    protected array $rendererAliases = [];

    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public function register(string $group, string $blockClass): static
    {
        $this->groups[$group][] = $blockClass;

        return $this;
    }

    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public function registerRendererAlias(string $blockClass): static
    {
        $this->rendererAliases[] = $blockClass;

        return $this;
    }

    /**
     * @return array<string, array<class-string<RichContentCustomBlock>>>
     */
    public function editorGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return array<class-string<RichContentCustomBlock>>
     */
    public function rendererBlocks(): array
    {
        $blocks = [];

        foreach ($this->groups as $groupBlocks) {
            foreach ($groupBlocks as $blockClass) {
                $blocks[] = $blockClass;
            }
        }

        return array_values(array_unique([...$blocks, ...$this->rendererAliases]));
    }
}
