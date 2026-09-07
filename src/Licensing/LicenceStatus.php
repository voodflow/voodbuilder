<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

/**
 * Licence Status.
 */
final class LicenceStatus
{
    public function __construct(
        public readonly string $edition,
        public readonly bool $active,
        public readonly ?string $identifier = null,
        public readonly ?string $expiresAt = null,
        public readonly ?string $message = null,
    ) {}

    public static function community(): self
    {
        return new self(
            edition: 'community',
            active: true,
            identifier: 'community',
            message: null,
        );
    }

    public function isPaidEdition(): bool
    {
        return in_array($this->edition, ['professional', 'agency'], true);
    }
}
