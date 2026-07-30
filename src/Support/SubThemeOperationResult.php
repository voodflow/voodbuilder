<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Sub Theme Operation Result.
 */
final readonly class SubThemeOperationResult
{
    public function __construct(
        public bool $success,
        public string $id,
        public ?string $error = null,
        public ?string $previousId = null,
    ) {}
}
