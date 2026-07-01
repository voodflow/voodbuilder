<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

class SitePageRevision extends Model
{
    public $timestamps = false;

    protected $table = 'voodbuilder_site_page_revisions';

    protected $fillable = [
        'site_page_id',
        'builder_payload',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'builder_payload' => 'array',
            'created_at' => 'datetime',
        ];
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
