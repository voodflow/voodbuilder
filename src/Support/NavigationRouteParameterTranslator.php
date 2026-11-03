<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Closure;

/**
 * Route-parameter translators for localized navigation links.
 *
 * A menu item pointing at a companion route (`events/{slug}`, `tutorials/{slug}`…)
 * carries route parameters that are language-specific. The package that owns the
 * route registers a translator here; VoodBuilder just asks every translator in turn
 * and never names a companion model.
 */
final class NavigationRouteParameterTranslator
{
    /** @var list<Closure(array<string, mixed>, string): mixed> */
    private array $translators = [];

    /**
     * A translator returns the rewritten parameters, or anything non-array to keep
     * the link untouched.
     *
     * @param  callable(array<string, mixed>, string): mixed  $translator
     */
    public function register(callable $translator): self
    {
        $this->translators[] = Closure::fromCallable($translator);

        return $this;
    }

    public function reset(): void
    {
        $this->translators = [];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function translate(array $parameters, string $locale): array
    {
        foreach ($this->translators as $translator) {
            try {
                $translated = $translator($parameters, $locale);
            } catch (\Throwable) {
                // A translator must never break menu rendering.
                continue;
            }

            if (is_array($translated)) {
                $parameters = $translated;
            }
        }

        return $parameters;
    }
}
