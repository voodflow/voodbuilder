<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Closure;
use Illuminate\Support\Collection;
use Voodflow\Vpress\Contracts\PublicContentChannel;

final class ConfiguredContentChannel implements PublicContentChannel
{
    /**
     * @param  list<string>  $routePatterns
     */
    public function __construct(
        private string $id,
        private string $label,
        private array $routePatterns,
        private ?string $subTheme,
        private ?Closure $searchResolver,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function routePatterns(): array
    {
        return $this->routePatterns;
    }

    public function subTheme(): ?string
    {
        return $this->subTheme;
    }

    public function search(string $term, int $limit = 20): Collection
    {
        if ($this->searchResolver === null) {
            return collect();
        }

        return ($this->searchResolver)($term, $limit);
    }
}
