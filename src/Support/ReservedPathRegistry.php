<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * First-segment URL prefixes that site-page catch-all routes must not claim.
 *
 * Companions (and third-party plugins) register their public prefixes here so
 * Voodbuilder stays agnostic of product paths like /docs or /tutorials.
 */
final class ReservedPathRegistry
{
    /** @var array<string, true> */
    private array $prefixes = [];

    public function reserve(string ...$prefixes): void
    {
        foreach ($prefixes as $prefix) {
            $segment = strtolower(trim($prefix, '/'));

            if ($segment === '' || str_contains($segment, '/')) {
                continue;
            }

            $this->prefixes[$segment] = true;
        }
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $prefixes = array_keys($this->prefixes);
        sort($prefixes);

        return $prefixes;
    }
}
