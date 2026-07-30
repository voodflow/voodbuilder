<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Sub Theme Import Result.
 */
final readonly class SubThemeImportResult
{
    public function __construct(
        public bool $success,
        public string $id,
        public ?string $error = null,
        public bool $configRegistered = false,
        public bool $importAppended = false,
        public bool $colorsImported = false,
        public ?string $cssPath = null,
        public ?string $renamedFrom = null,
    ) {}
}
