<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

/**
 * Edition → capability matrix from the 0.1.0 architecture plan (§7 / §11).
 * Community is the base matrix; Developer/Agency unlock additional surfaces.
 *
 * Runtime token remains `professional`; `developer` is accepted as an alias.
 */
final class EditionCapabilityMatrix
{
    public const EDITION_COMMUNITY = 'community';

    /** @deprecated Prefer {@see EDITION_DEVELOPER} in new copy; value stays `professional`. */
    public const EDITION_PROFESSIONAL = 'professional';

    public const EDITION_DEVELOPER = 'developer';

    public const EDITION_AGENCY = 'agency';

    /**
     * @return list<string>
     */
    public static function forEdition(string $edition): array
    {
        $edition = strtolower(trim($edition));

        return match ($edition) {
            self::EDITION_AGENCY => self::agency(),
            self::EDITION_PROFESSIONAL, self::EDITION_DEVELOPER => self::professional(),
            default => self::community(),
        };
    }

    public static function normalizeEdition(string $edition): string
    {
        $edition = strtolower(trim($edition));

        return match ($edition) {
            self::EDITION_AGENCY => self::EDITION_AGENCY,
            self::EDITION_PROFESSIONAL, self::EDITION_DEVELOPER => self::EDITION_PROFESSIONAL,
            default => self::EDITION_COMMUNITY,
        };
    }

    /**
     * Marketing label for splash / UI (Developer, not the runtime token `professional`).
     */
    public static function marketingLabel(string $edition): string
    {
        return match (self::normalizeEdition($edition)) {
            self::EDITION_AGENCY => (string) __('voodbuilder::pro.editor_ui.edition_agency'),
            self::EDITION_PROFESSIONAL => (string) __('voodbuilder::pro.editor_ui.edition_developer'),
            default => (string) __('voodbuilder::pro.editor_ui.edition_community'),
        };
    }

    /**
     * Public marketing slug (developer, not professional).
     */
    public static function marketingSlug(string $edition): string
    {
        return match (self::normalizeEdition($edition)) {
            self::EDITION_AGENCY => self::EDITION_AGENCY,
            self::EDITION_PROFESSIONAL => self::EDITION_DEVELOPER,
            default => self::EDITION_COMMUNITY,
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
            // Dynamic Data is a companion plugin (voodbuilder-dynamic-data), not Core.
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
            'dynamic-data.single',
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
            // Author JS in the public page. Top tier only, and off unless asked for: the
            // channel executes in every visitor's browser and no component type feeds it.
            'pages.custom-js',
            'dynamic-data.custom-providers',
            // Dynamic API companion (HTTP / remote sources) — Agency bundle.
            'dynamic-api.sources',
            'dynamic-api.admin',
            'marketplace.consume',
            'marketplace.submit',
            // Bundle includes vpopups commercially; capability already in community() for soft-gate UX.
        ]));
    }
}
