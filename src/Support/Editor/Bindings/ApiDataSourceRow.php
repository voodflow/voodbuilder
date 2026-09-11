<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Illuminate\Database\Eloquent\Model;

/**
 * In-memory Eloquent model standing in for one API / static data-source row in List repeat.
 *
 * Attributes: value, label, plus flattened meta keys for `{slug}.item.*` bindings.
 */
final class ApiDataSourceRow extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $table = 'voodbuilder_api_data_source_rows';

    public function __construct(
        array $attributes = [],
        public readonly string $sourceSlug = '',
    ) {
        parent::__construct($attributes);
        $this->exists = true;
        $this->wasRecentlyCreated = false;
    }

    /**
     * @param  array{value: mixed, label: string, meta: array<string, mixed>}  $row
     */
    public static function fromMappedRow(string $sourceSlug, array $row, int $index = 0): self
    {
        $meta = is_array($row['meta'] ?? null) ? $row['meta'] : [];
        $attributes = [
            'id' => (string) ($row['value'] ?? $index),
            'value' => $row['value'] ?? null,
            'label' => (string) ($row['label'] ?? ''),
        ];

        foreach ($meta as $key => $value) {
            $key = (string) $key;
            if ($key === '' || array_key_exists($key, $attributes)) {
                continue;
            }
            $attributes[$key] = $value;
        }

        return new self($attributes, $sourceSlug);
    }
}
