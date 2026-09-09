<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;
use Voodflow\Voodbuilder\Support\AdminAuthorization;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Components authoring unlock via voodflow/voodbuilder-components.
 *
 * The Filament companion plugin is the commercial gate (edition matrix alone is
 * only a soft upsell when the plugin is absent). When Shield is installed,
 * PageBuilderAccess / AdminAuthorization still apply.
 */
final class ComponentAuthoringBridge
{
    public static function isEnabled(): bool
    {
        return ComponentRuntimeBridge::moduleEnabled();
    }

    public static function canUseLibrary(): bool
    {
        return self::isEnabled() && PageBuilderAccess::userCanUsePageBuilder();
    }

    public static function canImport(): bool
    {
        return self::canUseLibrary() && self::passesOptionalShield('components.import');
    }

    public static function canExport(): bool
    {
        return self::canUseLibrary() && self::passesOptionalShield('components.export');
    }

    public static function canCodeImport(): bool
    {
        return self::canUseLibrary() && self::passesOptionalShield('components.code-import');
    }

    public static function authorizeLibrary(): void
    {
        abort_unless(
            ComponentsModule::isEnabled(),
            403,
            'Components module is disabled. Register VoodbuilderComponentsPlugin on your Filament panel.',
        );
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
    }

    public static function authorizeImport(): void
    {
        self::authorizeLibrary();
        abort_unless(self::canImport(), 403);
    }

    public static function authorizeExport(): void
    {
        self::authorizeLibrary();
        abort_unless(self::canExport(), 403);
    }

    public static function authorizeCodeImport(): void
    {
        self::authorizeLibrary();
        abort_unless(self::canCodeImport(), 403);
    }

    /**
     * Soft edition hints for hosts without the companion (upsell UI only).
     */
    public static function editionAllows(string $capability): bool
    {
        return Voodbuilder::can($capability);
    }

    /**
     * When Shield is present, honour an optional named ability if the host defined it.
     * Missing abilities do not block — PageBuilderAccess already gated the editor.
     */
    private static function passesOptionalShield(string $capability): bool
    {
        if (! AdminAuthorization::usesPermissionAuthorizer()) {
            return true;
        }

        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        // Prefer explicit Spatie/Shield abilities when registered; otherwise allow
        // any user who already passed PageBuilderAccess (panel / builder permission).
        $ability = match ($capability) {
            'components.import' => 'voodbuilder.components.import',
            'components.export' => 'voodbuilder.components.export',
            'components.code-import' => 'voodbuilder.components.code-import',
            default => null,
        };

        if ($ability === null || ! method_exists($user, 'can')) {
            return true;
        }

        if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
            return true;
        }

        $exists = \Spatie\Permission\Models\Permission::query()
            ->where('name', $ability)
            ->exists();

        if (! $exists) {
            return true;
        }

        return AdminAuthorization::allows($user, $ability);
    }
}
