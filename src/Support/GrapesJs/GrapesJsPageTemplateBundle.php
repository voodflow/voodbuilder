<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Models\PageTemplate;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;

final class GrapesJsPageTemplateBundle
{
    public const int FORMAT_VERSION = 1;

    public const string FORMAT = 'voodbuilder-page-templates';

    /**
     * @param  list<PageTemplate>  $templates
     * @return array<string, mixed>
     */
    public static function buildExportPayload(array $templates): array
    {
        return [
            'format_version' => self::FORMAT_VERSION,
            'format' => self::FORMAT,
            'exported_at' => now()->toIso8601String(),
            'generator' => [
                'name' => 'voodbuilder',
                'version' => VoodbuilderPackageVersion::current(),
            ],
            'templates' => array_map(
                static fn (PageTemplate $template): array => self::serializeTemplate($template),
                $templates,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeTemplate(PageTemplate $template): array
    {
        $entry = [
            'name' => $template->name,
            'category' => GrapesJsComponentCategoryNormalizer::normalize($template->category),
            'html' => $template->html,
            'css' => $template->css ?? '',
            'js' => $template->js ?? '',
        ];

        if (filled($template->description)) {
            $entry['description'] = $template->description;
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
                'import_meta' => __('voodbuilder::pro.page_templates.import_unsupported_format'),
            ]);
        }

        $formatVersion = (int) ($meta['format_version'] ?? $meta['version'] ?? 0);

        if ($formatVersion > self::FORMAT_VERSION) {
            throw ValidationException::withMessages([
                'import_meta' => __('voodbuilder::pro.page_templates.import_format_too_new'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public static function extractTemplates(array $payload): array
    {
        if (isset($payload['templates']) && is_array($payload['templates'])) {
            self::assertImportable($payload);

            return array_values(array_filter(
                $payload['templates'],
                static fn (mixed $entry): bool => is_array($entry),
            ));
        }

        if (isset($payload['html'], $payload['name']) && is_string($payload['html']) && is_string($payload['name'])) {
            return [$payload];
        }

        return [];
    }
}
