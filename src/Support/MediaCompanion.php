<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Is voodflow/vmedia taking care of media on this installation?
 *
 * The package is optional — it is not in this package's `require` — so every reference to
 * it has to survive its absence. Hence the string class name and the guards: the class may
 * not exist, and asking is not allowed to throw.
 *
 * **Never ask this question while Filament plugins are registering.** `Vmedia::activate()`
 * runs inside `VmediaPlugin::register()`, so the answer depends on which plugin the host
 * listed first in `->plugins([...])` — and in the common ordering, with VoodbuilderPlugin
 * before VmediaPlugin, the answer is a confident "no" that later becomes "yes". That is
 * exactly how the admin ended up with two "Media library" entries. Ask at access,
 * navigation or render time, when every plugin has had its turn.
 */
final class MediaCompanion
{
    private const CLASS_NAME = 'Voodflow\\Vmedia\\Vmedia';

    public static function isActive(): bool
    {
        if (! class_exists(self::CLASS_NAME) || ! method_exists(self::CLASS_NAME, 'isActive')) {
            return false;
        }

        try {
            return (bool) (self::CLASS_NAME)::isActive();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Does the companion own the media library in the admin sidebar?
     *
     * When it does, this package's own library resource stands down: two entries with the
     * same label, pointing at different tables, is worse than either one alone.
     *
     * Note this is not the same as "the package is installed". A host can install vmedia
     * for the editor's asset browser and choose not to register its Filament plugin; in
     * that case nothing owns the sidebar and our resource stays.
     */
    public static function ownsAdminLibrary(): bool
    {
        return self::isActive();
    }
}
