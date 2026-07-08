<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_page_templates';

    protected $fillable = [
        'name',
        'category',
        'description',
        'html',
        'css',
        'js',
    ];

    /**
     * @return array{html: string, css: string, js: string}
     */
    public function builderPayload(): array
    {
        return [
            'html' => (string) $this->html,
            'css' => (string) ($this->css ?? ''),
            'js' => (string) ($this->js ?? ''),
        ];
    }
}
