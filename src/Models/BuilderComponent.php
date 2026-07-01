<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BuilderComponent extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_components';

    protected $fillable = [
        'name',
        'category',
        'description',
        'html',
        'css',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * @return list<array{id: string, label: string, type: string, default?: mixed}>
     */
    public function propertySchema(): array
    {
        $properties = $this->properties;

        return is_array($properties) ? $properties : [];
    }
}
