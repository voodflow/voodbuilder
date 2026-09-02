<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Enums\SitePageRevisionKind;

/**
 * Site Page Revision.
 */
class SitePageRevision extends Model
{
    public $timestamps = false;

    protected $table = 'voodbuilder_site_page_revisions';

    protected $fillable = [
        'site_page_id',
        'kind',
        'builder_payload',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => SitePageRevisionKind::class,
            'builder_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @param Builder<$this> $query */
    public function scopeManual(Builder $query): void
    {
        $query->where('kind', SitePageRevisionKind::Manual);
    }

    /** @param Builder<$this> $query */
    public function scopeAutosaves(Builder $query): void
    {
        $query->where('kind', SitePageRevisionKind::Autosave);
    }

    /** @return BelongsTo<SitePage, $this> */
    public function sitePage(): BelongsTo
    {
        return $this->belongsTo(SitePage::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
