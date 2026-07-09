<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BuilderPopup extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_popups';

    protected $fillable = [
        'name',
        'enabled',
        'priority',
        'rules',
        'html',
        'css',
        'js',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'priority' => 'integer',
            'rules' => 'array',
        ];
    }

    /**
     * @return array{html: string, css: string, js: string}
     */
    public function builderPayload(): array
    {
        return [
            'html' => (string) ($this->html ?? ''),
            'css' => (string) ($this->css ?? ''),
            'js' => (string) ($this->js ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedRules(): array
    {
        $rules = is_array($this->rules) ? $this->rules : [];

        return array_replace_recursive(self::defaultRules(), $rules);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultRules(): array
    {
        return [
            'trigger' => [
                'type' => 'delay',
                'delay_seconds' => 3,
                'scroll_percent' => 50,
                'click_selector' => '',
            ],
            'frequency' => [
                'mode' => 'session',
                'days' => 7,
            ],
            'targeting' => [
                'match' => 'any',
                'sets' => [],
            ],
            'display' => [
                'width' => 'md',
                'overlay' => true,
                'close_on_overlay' => true,
                'close_on_escape' => true,
            ],
        ];
    }
}
