<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

/**
 * Site Page Revision Kind enumeration.
 *
 * Manual revisions are the history an author reasons about. Autosaves are a safety net for
 * work that was never saved, so they are budgeted, pruned and listed separately — mixing
 * them would let unattended writes evict every real save point.
 */
enum SitePageRevisionKind: string
{
    case Manual = 'manual';
    case Autosave = 'autosave';

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('voodbuilder::pro.revisions.kind_manual'),
            self::Autosave => __('voodbuilder::pro.revisions.kind_autosave'),
        };
    }

    /**
     * Rows written before this column existed are deliberate saves.
     */
    public static function normalize(mixed $state): self
    {
        if ($state instanceof self) {
            return $state;
        }

        if (blank($state)) {
            return self::Manual;
        }

        return self::tryFrom((string) $state) ?? self::Manual;
    }

    public function maxToKeep(): int
    {
        return match ($this) {
            self::Manual => (int) config('voodbuilder.editor.revisions.max_to_keep', 50),
            self::Autosave => (int) config('voodbuilder.editor.revisions.max_autosaves_to_keep', 5),
        };
    }
}
