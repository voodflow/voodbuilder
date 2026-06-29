<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;

final class GrapesJsServerBlockRegistry
{
    /** @var array<string, class-string<GrapesJsServerBlock>> */
    protected array $blocks = [];

    /** @var array<string, string> */
    protected array $categories = [];

    /**
     * @param  class-string<GrapesJsServerBlock>  $blockClass
     */
    public function register(string $category, string $blockClass): self
    {
        $this->blocks[$blockClass::getId()] = $blockClass;
        $this->categories[$blockClass::getId()] = $category;

        return $this;
    }

    /**
     * @return array<string, class-string<GrapesJsServerBlock>>
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
     * @return class-string<GrapesJsServerBlock>|null
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
                GrapesJsServerBlockAdapter::toDefinition($blockClass, $category, $eventId),
            );
        }
    }
}
