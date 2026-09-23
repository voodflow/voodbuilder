<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\PublicDiskUrl;

/**
 * Persist large compiled page stylesheets on the public disk so Save JSON and
 * `builder_payload` stay under shared-host body / Laravel `max:` limits.
 *
 * Small sheets stay inline in `builder_payload.css` (legacy / simple pages).
 * Large sheets: author rules in `css`, full sheet at `css_artifact.path`.
 */
final class PageCssArtifactStore
{
    public const META_KEY = 'css_artifact';

    /**
     * Split a fully resolved stylesheet into payload fields suitable for storage.
     *
     * @return array{css: string, css_artifact: array{path: string, hash: string, bytes: int}|null}
     */
    public static function persistForPage(SitePage $page, string $fullCss, ?string $authorCss = null): array
    {
        $fullCss = trim($fullCss);
        $author = trim((string) ($authorCss ?? EditorPastedComponentNormalizer::manualPageCssFromStoredCss($fullCss)));
        $threshold = self::inlineThresholdBytes();

        if ($fullCss === '' || strlen($fullCss) <= $threshold) {
            self::deleteLiveArtifact($page);

            return [
                'css' => $fullCss,
                self::META_KEY => null,
            ];
        }

        $path = self::liveRelativePath((int) $page->getKey());
        $hash = hash('sha256', $fullCss);
        $disk = self::disk();
        $disk->put($path, $fullCss);

        return [
            'css' => $author,
            self::META_KEY => [
                'path' => $path,
                'hash' => $hash,
                'bytes' => strlen($fullCss),
            ],
        ];
    }

    /**
     * Ensure a revision/autosave payload does not embed a megabyte stylesheet.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function slimPayloadForHistory(array $payload, int $pageId, string $suffix): array
    {
        $css = trim((string) ($payload['css'] ?? ''));
        $existing = self::metaFromPayload($payload);

        if ($existing !== null) {
            return $payload;
        }

        if ($css === '' || strlen($css) <= self::inlineThresholdBytes()) {
            return $payload;
        }

        $author = EditorPastedComponentNormalizer::manualPageCssFromStoredCss($css);
        $path = self::revisionRelativePath($pageId, $suffix);
        $disk = self::disk();
        $disk->put($path, $css);

        $payload['css'] = $author;
        $payload[self::META_KEY] = [
            'path' => $path,
            'hash' => hash('sha256', $css),
            'bytes' => strlen($css),
        ];

        return $payload;
    }

    /**
     * Full published CSS for a payload (artifact file or inline `css`).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function resolveCss(array $payload): string
    {
        $meta = self::metaFromPayload($payload);

        if ($meta !== null) {
            $fromDisk = self::readPath($meta['path']);

            if ($fromDisk !== null && $fromDisk !== '') {
                return $fromDisk;
            }
        }

        return trim((string) ($payload['css'] ?? ''));
    }

    /**
     * Public URL for a page CSS artifact, or null when CSS is inline.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function publicUrl(array $payload): ?string
    {
        $meta = self::metaFromPayload($payload);

        if ($meta === null) {
            return null;
        }

        if (! self::disk()->exists($meta['path'])) {
            return null;
        }

        $url = PublicDiskUrl::fromPath($meta['path']);
        $hash = $meta['hash'] ?? '';

        if (is_string($hash) && $hash !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?').'v='.substr($hash, 0, 12);
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{path: string, hash?: string, bytes?: int}|null
     */
    public static function metaFromPayload(array $payload): ?array
    {
        $meta = $payload[self::META_KEY] ?? null;

        if (! is_array($meta)) {
            return null;
        }

        $path = trim((string) ($meta['path'] ?? ''));

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return [
            'path' => $path,
            'hash' => isset($meta['hash']) ? (string) $meta['hash'] : '',
            'bytes' => isset($meta['bytes']) ? (int) $meta['bytes'] : 0,
        ];
    }

    public static function deleteLiveArtifact(SitePage $page): void
    {
        $path = self::liveRelativePath((int) $page->getKey());
        $disk = self::disk();

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * Best-effort cleanup when a revision row is discarded.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function deleteArtifactFromPayload(array $payload): void
    {
        $meta = self::metaFromPayload($payload);

        if ($meta === null) {
            return;
        }

        // Never delete the live page sheet from a revision trim.
        if (preg_match('#/page-\d+\.css$#', $meta['path']) === 1
            && ! str_contains($meta['path'], '-r')) {
            return;
        }

        $disk = self::disk();

        if ($disk->exists($meta['path'])) {
            $disk->delete($meta['path']);
        }
    }

    public static function inlineThresholdBytes(): int
    {
        return max(1, (int) config('voodbuilder.editor.payload.css_artifact_threshold_bytes', 250_000));
    }

    public static function liveRelativePath(int $pageId): string
    {
        return self::directory().'/page-'.$pageId.'.css';
    }

    public static function revisionRelativePath(int $pageId, string $suffix): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $suffix) ?: 'rev';

        return self::directory().'/page-'.$pageId.'-r'.$safe.'.css';
    }

    private static function directory(): string
    {
        return trim((string) config('voodbuilder.editor.payload.css_artifact_directory', 'voodbuilder/page-css'), '/');
    }

    private static function disk(): Filesystem
    {
        return Storage::disk((string) config('voodbuilder.editor.payload.css_artifact_disk', 'public'));
    }

    private static function readPath(string $path): ?string
    {
        $disk = self::disk();

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);

        return is_string($contents) ? trim($contents) : null;
    }
}
