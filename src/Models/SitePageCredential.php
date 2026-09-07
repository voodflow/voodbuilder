<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Email/password (or password-only) credential for a password-protected site page.
 */
class SitePageCredential extends Model
{
    protected $fillable = [
        'site_page_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    /** @return BelongsTo<SitePage, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }
}
