<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Storage;

/**
 * User Avatar.
 */
final class UserAvatar
{
    public static function url(?Authenticatable $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if ($user instanceof HasAvatar) {
            return $user->getFilamentAvatarUrl();
        }

        if (blank($user->avatar ?? null)) {
            return null;
        }

        $disk = (string) config('voodbuilder.account.avatar.disk', 'public');

        return Storage::disk($disk)->url((string) $user->avatar);
    }
}
