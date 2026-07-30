<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Fonts;

/**
 * Immutable font entry for the editor catalog / publish pipeline.
 *
 * @phpstan-type FontArray array{
 *     id: string,
 *     family: string,
 *     category: string,
 *     provider: string,
 *     package: ?string,
 *     files: list<string>,
 *     stack: string,
 *     weights: list<int>,
 *     meta: array<string, mixed>
 * }
 */
final class FontDefinition
{
    /**
     * @param  list<string>  $files
     * @param  list<int>  $weights
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $id,
        public readonly string $family,
        public readonly string $category,
        public readonly string $provider,
        public readonly ?string $package,
        public readonly array $files,
        public readonly string $stack,
        public readonly array $weights = [400],
        public readonly array $meta = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $id = trim((string) ($data['id'] ?? ''));
        $family = trim((string) ($data['family'] ?? ''));
        $stack = trim((string) ($data['stack'] ?? ''));

        if ($id === '' || $family === '' || $stack === '') {
            throw new \InvalidArgumentException('FontDefinition requires id, family, and stack.');
        }

        $files = array_values(array_filter(array_map(
            static fn (mixed $file): string => trim((string) $file),
            is_array($data['files'] ?? null) ? $data['files'] : [],
        )));

        $weights = array_values(array_filter(array_map(
            static fn (mixed $weight): int => (int) $weight,
            is_array($data['weights'] ?? null) ? $data['weights'] : [400],
        )));

        return new self(
            id: $id,
            family: $family,
            category: trim((string) ($data['category'] ?? 'sans-serif')) ?: 'sans-serif',
            provider: trim((string) ($data['provider'] ?? 'fontsource')) ?: 'fontsource',
            package: filled($data['package'] ?? null) ? trim((string) $data['package']) : null,
            files: $files,
            stack: self::cssSafeStack($stack),
            weights: $weights !== [] ? $weights : [400],
            meta: is_array($data['meta'] ?? null) ? $data['meta'] : [],
        );
    }

    /**
     * Prefer single quotes around multi-word families so stacks survive inline
     * style="..." attributes (nested double quotes break HTML).
     */
    public static function cssSafeStack(string $stack): string
    {
        $trimmed = trim((string) preg_replace('/\s*!important\s*$/i', '', trim($stack)));

        if ($trimmed === '') {
            return '';
        }

        if (! str_contains($trimmed, '"')) {
            return $trimmed;
        }

        return (string) preg_replace('/"([^"]+)"/', "'$1'", $trimmed);
    }

    /**
     * @return FontArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'family' => $this->family,
            'category' => $this->category,
            'provider' => $this->provider,
            'package' => $this->package,
            'files' => $this->files,
            'stack' => $this->stack,
            'weights' => $this->weights,
            'meta' => $this->meta,
        ];
    }

    /**
     * GrapesJS Style Manager option shape.
     *
     * @return array{id: string, label: string}
     */
    public function toStyleManagerOption(): array
    {
        return [
            'id' => $this->stack,
            'label' => $this->family,
        ];
    }
}
