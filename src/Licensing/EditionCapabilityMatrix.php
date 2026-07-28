<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

/**
 * Edition → capability matrix from the 0.1.0 architecture plan (§7 / §11).
 * Community stays fully useful; Pro/Agency unlock proprietary surfaces.
 */
final class EditionCapabilityMatrix
{
    public const EDITION_COMMUNITY = 'community';

    public const EDITION_PROFESSIONAL = 'professional';

    public const EDITION_AGENCY = 'agency';

    /**
     * @return list<string>
     */
    public static function forEdition(string $edition): array
    {
        $edition = strtolower(trim($edition));

        return match ($edition) {
            self::EDITION_AGENCY => self::agency(),
            self::EDITION_PROFESSIONAL => self::professional(),
            default => self::community(),
        };
    }

    /**
     * @return list<string>
     */
    public static function community(): array
    {
        return [
            'editor.core',
            'editor.animations',
            'editor.conditions',
            'editor.history',
            'blocks.core',
            'templates.local',
            'themes.clone',
            'themes.map',
            'themes.studio',
            'dynamic-data.single',
            'menus.admin',
            'menus.preview',
            'layouts.chrome',
            'layouts.editor',
            'pages.admin',
            'pages.editor',
            'pages.forms',
            'popups.admin',
            'popups.editor',
            'popups.runtime',
            'popups.analytics',
            'popups.builder',
        ];
    }

    /**
     * @return list<string>
     */
    public static function professional(): array
    {
        return array_values(array_unique([
            ...self::community(),
            'blocks.official.complete',
            'templates.import',
            'templates.remote-install',
            'themes.import',
            'dynamic-data.collections',
            'dynamic-data.query-builder',
        ]));
    }

    /**
     * @return list<string>
     */
    public static function agency(): array
    {
        return array_values(array_unique([
            ...self::professional(),
            'templates.export',
            'templates.marketplace-submit',
            'themes.export',
            'themes.team-share',
            'components.library',
            'components.create',
            'components.import',
            'components.export',
            'components.code-import',
            'components.import-export',
            'components.global-classes',
            'components.team-share',
            'dynamic-data.custom-providers',
            'marketplace.consume',
            'marketplace.submit',
        ]));
    }
}
