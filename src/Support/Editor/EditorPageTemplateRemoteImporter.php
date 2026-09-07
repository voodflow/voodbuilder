<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Support\SafeRemoteUrl;

/**
 * Editor Page Template Remote Importer.
 */
final class EditorPageTemplateRemoteImporter
{
    private const int MAX_BYTES = 2_000_000;

    /**
     * @return array<string, mixed>
     */
    public static function fetchBundle(string $url): array
    {
        $normalizedUrl = self::normalizeUrl($url);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($normalizedUrl);
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_url_unreachable'),
            ], previous: $exception);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_url_failed', [
                    'status' => $response->status(),
                ]),
            ]);
        }

        $body = (string) $response->body();

        if (strlen($body) > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_url_too_large'),
            ]);
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_invalid_file'),
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_invalid_file'),
            ]);
        }

        EditorPageTemplateBundle::assertImportable($decoded);

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fetchCatalog(string $url): array
    {
        $decoded = self::fetchBundle($url);
        $entries = EditorPageTemplateBundle::extractCatalogEntries($decoded);

        return array_values(array_map(
            static function (array $entry): array {
                $hasInlineHtml = filled($entry['html'] ?? null);

                return [
                    'name' => (string) ($entry['name'] ?? ''),
                    'category' => filled($entry['category'] ?? null) ? (string) $entry['category'] : null,
                    'description' => filled($entry['description'] ?? null) ? (string) $entry['description'] : null,
                    'bundle_url' => filled($entry['bundle_url'] ?? null) ? (string) $entry['bundle_url'] : null,
                    'preview_url' => filled($entry['preview_url'] ?? null) ? (string) $entry['preview_url'] : null,
                    'price_label' => filled($entry['price_label'] ?? null) ? (string) $entry['price_label'] : null,
                    'installable_inline' => $hasInlineHtml,
                ];
            },
            $entries,
        ));
    }

    public static function normalizeUrl(string $url): string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_url_required'),
            ]);
        }

        $parts = parse_url($trimmed);

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https') {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_url_https_only'),
            ]);
        }

        if (SafeRemoteUrl::isBlockedHost((string) ($parts['host'] ?? ''))) {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_url_blocked'),
            ]);
        }

        return $trimmed;
    }
}
