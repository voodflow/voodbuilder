<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Models\BuilderComponent;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;

final class GrapesJsComponentBundle
{
    public const int FORMAT_VERSION = 1;

    public const string FORMAT = 'voodbuilder-components';

    /**
     * @param  list<BuilderComponent>  $components
     * @return array<string, mixed>
     */
    public static function buildExportPayload(array $components): array
    {
        return [
            'format_version' => self::FORMAT_VERSION,
            'format' => self::FORMAT,
            'exported_at' => now()->toIso8601String(),
            'generator' => [
                'name' => 'voodbuilder',
                'version' => VoodbuilderPackageVersion::current(),
            ],
            'components' => array_map(
                static fn (BuilderComponent $component): array => self::serializeComponent($component),
                $components,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeComponent(BuilderComponent $component): array
    {
        $html = GrapesJsComponentExportNormalizer::htmlForExport(
            VoodbuilderThemeTokenMigrator::migrateHtml((string) $component->html),
        );
        $css = GrapesJsPastedComponentNormalizer::cssForExport(
            GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml(
                $html,
                $component->css,
                $component->html_checksum,
            ),
            $html,
        );

        $properties = $component->propertySchema();

        $entry = [
            'name' => $component->name,
            'category' => GrapesJsComponentCategoryNormalizer::normalize($component->category),
            'html' => $html,
        ];

        if (filled($component->description)) {
            $entry['description'] = $component->description;
        }

        if ($css !== null && $css !== '') {
            $entry['css'] = $css;
        }

        if ($properties !== []) {
            $entry['properties'] = $properties;
        }

        return $entry;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function assertImportable(?array $meta): void
    {
        if ($meta === null || $meta === []) {
            return;
        }

        $format = (string) ($meta['format'] ?? '');

        if ($format !== '' && $format !== self::FORMAT) {
            throw ValidationException::withMessages([
                'import_meta' => __('voodbuilder::pro.components.import_unsupported_format'),
            ]);
        }

        $formatVersion = (int) ($meta['format_version'] ?? $meta['version'] ?? 0);

        if ($formatVersion > self::FORMAT_VERSION) {
            throw ValidationException::withMessages([
                'import_meta' => __('voodbuilder::pro.components.import_format_too_new'),
            ]);
        }

        $generator = $meta['generator'] ?? null;

        if (! is_array($generator)) {
            return;
        }

        $generatorName = (string) ($generator['name'] ?? '');
        $generatorVersion = (string) ($generator['version'] ?? '');

        if ($generatorName !== '' && $generatorName !== 'voodbuilder') {
            return;
        }

        if ($generatorVersion === '') {
            return;
        }

        if (VoodbuilderPackageVersion::isNewerThan($generatorVersion, VoodbuilderPackageVersion::current())) {
            throw ValidationException::withMessages([
                'import_meta' => __('voodbuilder::pro.components.import_generator_too_new', [
                    'required' => VoodbuilderPackageVersion::normalize($generatorVersion),
                    'installed' => VoodbuilderPackageVersion::current(),
                ]),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public static function extractComponents(array $payload): array
    {
        if (isset($payload['components']) && is_array($payload['components'])) {
            self::assertImportable($payload);

            return array_values(array_filter(
                $payload['components'],
                static fn (mixed $entry): bool => is_array($entry),
            ));
        }

        if (isset($payload['html'], $payload['name']) && is_string($payload['html']) && is_string($payload['name'])) {
            return [$payload];
        }

        return [];
    }
}
