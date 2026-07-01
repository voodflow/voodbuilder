<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BuilderGlobalClass extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_global_classes';

    protected $fillable = [
        'name',
        'label',
        'css',
        'locked',
    ];

    protected function casts(): array
    {
        return [
            'locked' => 'boolean',
        ];
    }
}
