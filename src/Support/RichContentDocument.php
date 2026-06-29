<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final class RichContentDocument
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    public static function fromBlocks(array $blocks): array
    {
        return [
            'type' => 'doc',
            'content' => $blocks,
        ];
    }

    /** @param  array<string, mixed>  $config */
    public static function customBlock(string $id, array $config): array
    {
        return [
            'type' => 'customBlock',
            'attrs' => [
                'id' => $id,
                'config' => $config,
            ],
        ];
    }
}
