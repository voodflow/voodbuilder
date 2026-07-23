<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Voodflow\Voodbuilder\Enums\PopupAnalyticsEvent;

class BuilderPopupEvent extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_popup_events';

    protected $fillable = [
        'popup_id',
        'event',
        'close_reason',
        'page_path',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => PopupAnalyticsEvent::class,
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BuilderPopup, $this>
     */
    public function popup(): BelongsTo
    {
        return $this->belongsTo(BuilderPopup::class, 'popup_id');
    }
}
