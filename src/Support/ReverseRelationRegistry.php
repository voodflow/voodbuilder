<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

/**
 * Reverse relation descriptors (optional). Vpress ships a no-op registry so the
 * Model Integration UI stays compatible without requiring Voodflow.
 */
final class ReverseRelationRegistry
{
    /**
     * @param  class-string|null  $targetModel
     * @return array<int, array<string, mixed>>
     */
    public function for(?string $targetModel): array
    {
        return [];
    }

    public function find(?string $descriptorKey): ?array
    {
        return null;
    }
}
