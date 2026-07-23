<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuilderPopup extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_popups';

    protected $fillable = [
        'name',
        'description',
        'locale',
        'enabled',
        'paused',
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
            'paused' => 'boolean',
            'priority' => 'integer',
            'rules' => 'array',
        ];
    }

    /**
     * @return HasMany<BuilderPopupEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(BuilderPopupEvent::class, 'popup_id');
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
            'schedule' => [
                'start_at' => null,
                'end_at' => null,
                'weekly_days' => [],
                'weekly_day' => '',
                'timezone' => null,
                'weekly_start_time' => '',
                'weekly_end_time' => '',
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
