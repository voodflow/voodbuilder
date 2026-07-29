<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Contracts\EditorServerBlock;

final class EditorServerBlockRegistry
{
    /** @var array<string, class-string<EditorServerBlock>> */
    protected array $blocks = [];

    /** @var array<string, string> */
    protected array $categories = [];

    /**
     * @param  class-string<EditorServerBlock>  $blockClass
     */
    public function register(string $category, string $blockClass): self
    {
        $this->blocks[$blockClass::getId()] = $blockClass;
        $this->categories[$blockClass::getId()] = $category;

        return $this;
    }

    /**
     * @return array<string, class-string<EditorServerBlock>>
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
     * @return class-string<EditorServerBlock>|null
     */
    public function resolve(string $blockId): ?string
    {
        if (isset($this->blocks[$blockId])) {
            return $this->blocks[$blockId];
        }

        $legacyId = EditorLegacySiteBlockMap::resolve($blockId);

        if ($legacyId !== null) {
            return $this->blocks[$legacyId] ?? null;
        }

        return null;
    }

    public function registerEditorBlocks(EditorBlockRegistry $registry, ?int $eventId = null): void
    {
        foreach ($this->blocks as $blockId => $blockClass) {
            $category = $this->categories[$blockId] ?? 'Dynamic';

            $registry->register(
                EditorServerBlockAdapter::toDefinition($blockClass, $category, $eventId),
            );
        }
    }
}
