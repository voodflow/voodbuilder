<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Optional channel stylesheets shipped by companion packages.
 */
final class ChannelStylesheetRegistry
{
    /** @var list<string> */
    private array $absolutePaths = [];

    public function register(string $absolutePath): void
    {
        if (! is_file($absolutePath)) {
            return;
        }

        $this->absolutePaths[] = $absolutePath;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return array_values(array_unique($this->absolutePaths));
    }
}
