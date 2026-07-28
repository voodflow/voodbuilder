<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing\Contracts;

use RuntimeException;

final class LicenceClientException extends RuntimeException
{
    public static function unreachable(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }
}
