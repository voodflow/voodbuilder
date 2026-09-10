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
            $request = Http::timeout(15)
                ->withHeaders(['Accept' => 'application/json']);

            $token = self::catalogToken();

            if ($token !== '') {
                $request = $request->withHeaders([
                    'X-VoodBuilder-Catalog-Token' => $token,
                ]);
            }

            $response = $request->get($normalizedUrl);
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
            static function (array $entry) use ($url): array {
                $hasInlineHtml = filled($entry['html'] ?? null);
                $bundleUrl = filled($entry['bundle_url'] ?? null) ? (string) $entry['bundle_url'] : null;

                // Keep relative paths relative so the browser never sees the CDN host.
                // Absolute URLs are rewritten to a path-only form when they share the catalog host.
                if (is_string($bundleUrl) && str_starts_with($bundleUrl, 'https://')) {
                    $bundleUrl = self::toCatalogRelativePath($url, $bundleUrl) ?? $bundleUrl;
                }

                return [
                    'name' => (string) ($entry['name'] ?? ''),
                    'category' => filled($entry['category'] ?? null) ? (string) $entry['category'] : null,
                    'description' => filled($entry['description'] ?? null) ? (string) $entry['description'] : null,
                    'bundle_url' => $bundleUrl,
                    'preview_url' => null,
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

        // Relative catalog assets resolve against the configured catalog directory.
        if (! str_starts_with($trimmed, 'https://') && ! str_starts_with($trimmed, 'http://')) {
            $catalogUrl = trim((string) config('voodbuilder.page_templates.catalog_url', ''));

            if ($catalogUrl === '') {
                throw ValidationException::withMessages([
                    'url' => __('voodbuilder::pro.page_templates.import_url_required'),
                ]);
            }

            $trimmed = rtrim(self::directoryOf($catalogUrl), '/').'/'.ltrim($trimmed, '/');
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

    protected static function catalogToken(): string
    {
        if (class_exists(\Voodflow\Voodbuilder\Licensing\CatalogCredentialResolver::class)) {
            return \Voodflow\Voodbuilder\Licensing\CatalogCredentialResolver::pageTemplatesCredential() ?? '';
        }

        return trim((string) (
            config('voodbuilder.page_templates.catalog_token')
            ?: config('voodbuilder-elements.catalog_token')
            ?: ''
        ));
    }

    protected static function directoryOf(string $url): string
    {
        return (string) preg_replace('#/[^/]*$#', '', $url);
    }

    protected static function toCatalogRelativePath(string $catalogUrl, string $absoluteUrl): ?string
    {
        $base = rtrim(self::directoryOf($catalogUrl), '/').'/';

        if (! str_starts_with($absoluteUrl, $base)) {
            return null;
        }

        return ltrim(substr($absoluteUrl, strlen($base)), '/');
    }
}
