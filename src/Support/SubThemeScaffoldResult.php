<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final readonly class SubThemeScaffoldResult
{
    public function __construct(
        public bool $success,
        public string $id,
        public ?string $error = null,
        public bool $configRegistered = false,
        public bool $importAppended = false,
        public ?string $cssPath = null,
    ) {}
}
