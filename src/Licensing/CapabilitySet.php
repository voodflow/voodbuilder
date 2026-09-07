<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

/**
 * Immutable set of capability identifiers (e.g. components.export).
 */
final class CapabilitySet
{
    /** @param array<string, true> $capabilities */
    private function __construct(
        private array $capabilities,
    ) {}

    /**
     * @param  list<string>  $capabilities
     */
    public static function from(array $capabilities): self
    {
        $map = [];

        foreach ($capabilities as $capability) {
            $capability = trim((string) $capability);

            if ($capability === '') {
                continue;
            }

            $map[$capability] = true;
        }

        return new self($map);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function has(string $capability): bool
    {
        return isset($this->capabilities[$capability]);
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $keys = array_keys($this->capabilities);
        sort($keys);

        return $keys;
    }

    public function merge(self $other): self
    {
        return new self($this->capabilities + $other->capabilities);
    }

    public function count(): int
    {
        return count($this->capabilities);
    }
}
