<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;

/**
 * Chrome Layout.
 */
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
        'reading_font',
        'reading_font_size',
        'reading_sidebar_font',
        'reading_type_scale',
        'reading_sidebar_type_scale',
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
            'reading_type_scale' => 'array',
            'reading_sidebar_type_scale' => 'array',
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

        // Default is a fallback only — never an explicit channel claim.
        if ($this->is_default) {
            return false;
        }

        return in_array($channelId, $this->assignedChannelIds(), true);
    }

    /**
     * Chrome layouts saved from Theme Studio before any header/footer blocks are added
     * contain only editor drop zones — treat them as unpublished and fall back to default.
     */
    public function isDraftShell(): bool
    {
        $html = (string) ($this->html ?? '');

        if ($html === '') {
            return true;
        }

        return str_contains($html, 'voodbuilder-chrome-drop-zone')
            && ! str_contains($html, 'data-voodbuilder-block');
    }

    /**
     * Ensure at most one layout is marked as the site-wide default.
     */
    public static function ensureSingleDefault(?self $layout): void
    {
        if ($layout === null || ! $layout->is_default) {
            return;
        }

        static::query()
            ->whereKeyNot($layout->getKey())
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
