<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;

class ChromeLayout extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_chrome_layouts';

    protected $fillable = [
        'name',
        'slug',
        'html',
        'css',
        'js',
        'enabled',
        'is_default',
        'channel_ids',
        'content_width',
        'content_max_width',
        'chrome_width',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'is_default' => 'boolean',
            'channel_ids' => 'array',
        ];
    }

    /**
     * @return array{mode: string, maxWidth: string|null}
     */
    public function resolvedContentWidth(): array
    {
        return ChromeLayoutContentWidth::fromLayout($this);
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
     * @return list<string>
     */
    public function assignedChannelIds(): array
    {
        $channels = $this->channel_ids ?? [];

        if (! is_array($channels)) {
            return [];
        }

        return array_values(array_filter($channels, static fn ($id): bool => is_string($id) && $id !== ''));
    }

    public function appliesToChannel(?string $channelId): bool
    {
        if ($channelId === null || $channelId === '') {
            return $this->is_default;
        }

        return in_array($channelId, $this->assignedChannelIds(), true);
    }
}
