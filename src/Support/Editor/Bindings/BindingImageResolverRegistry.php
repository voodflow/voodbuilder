<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Illuminate\Database\Eloquent\Model;

/**
 * Level 4 — plugin-specific image URL hooks for exotic storage (CDN, signed URLs, etc.).
 */
final class BindingImageResolverRegistry
{
    /** @var array<class-string<Model>, array<string, callable(Model, BindingContext): (?string)>> */
    private array $resolvers = [];

    /**
     * @param  callable(Model, BindingContext): (?string)  $resolver
     */
    public function register(string $modelClass, string $fieldId, callable $resolver): void
    {
        $this->resolvers[$modelClass][$fieldId] = $resolver;
    }

    public function resolve(Model $record, string $fieldId, BindingContext $context): ?string
    {
        $resolver = $this->resolvers[$record::class][$fieldId] ?? null;

        if ($resolver === null) {
            return null;
        }

        $url = $resolver($record, $context);

        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        return trim($url);
    }
}
