<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Support\PublicDiskUrl;

/**
 * Resolve editor-safe image URLs across plugins (public disk paths, Spatie Media Library, etc.).
 */
final class BindingMediaUrlResolver
{
    public static function resolve(
        Model $record,
        string $fieldId,
        ?string $resolvedValue = null,
        bool|BindingContext $previewContext = false,
    ): ?string {
        $context = self::bindingContext($previewContext);

        $custom = app(BindingImageResolverRegistry::class)->resolve($record, $fieldId, $context);

        if ($custom !== null) {
            return self::normalizeForEditor($custom);
        }

        $fromMedia = self::resolveFromMediaLibrary($record, $fieldId, $context->editorPreview);

        if ($fromMedia !== null) {
            return $fromMedia;
        }

        $urlAccessor = Str::camel($fieldId).'Url';

        if (method_exists($record, $urlAccessor)) {
            $accessorUrl = $record->{$urlAccessor}();

            if (is_string($accessorUrl) && $accessorUrl !== '') {
                return self::normalizeForEditor($accessorUrl);
            }
        }

        if ($resolvedValue === null || $resolvedValue === '') {
            return null;
        }

        return self::normalizeForEditor($resolvedValue);
    }

    public static function editorMediaPreviewPath(int $mediaId): string
    {
        return '/voodbuilder/grapesjs/media/'.$mediaId;
    }

    public static function normalizeForEditor(string $value): string
    {
        if (str_starts_with($value, 'data:')) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        if (str_starts_with($value, '/voodbuilder/grapesjs/media/')) {
            return $value;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = parse_url($value, PHP_URL_PATH);

            if (is_string($path) && (str_starts_with($path, '/storage/') || str_starts_with($path, '/voodbuilder/grapesjs/media/'))) {
                return $path;
            }

            return $value;
        }

        if (! preg_match('#^(https?://|data:|/)#i', $value)) {
            return PublicDiskUrl::fromPath($value);
        }

        return $value;
    }

    public static function resolveFromMediaLibrary(
        Model $record,
        string $fieldId,
        bool $forEditorPreview = false,
    ): ?string {
        if (! method_exists($record, 'hasMedia') || ! method_exists($record, 'getFirstMedia')) {
            return null;
        }

        foreach (self::candidateCollections($fieldId) as $collection) {
            if (! $record->hasMedia($collection)) {
                continue;
            }

            $url = self::mediaToEditorUrl($record->getFirstMedia($collection), $forEditorPreview);

            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    protected static function mediaToEditorUrl(mixed $media, bool $forEditorPreview = false): ?string
    {
        if (! is_object($media) || ! method_exists($media, 'getPathRelativeToRoot')) {
            return null;
        }

        $diskName = (string) ($media->disk ?? '');

        if ($diskName === 'public') {
            return PublicDiskUrl::fromPath((string) $media->getPathRelativeToRoot());
        }

        if ($forEditorPreview && method_exists($media, 'getKey')) {
            return self::editorMediaPreviewPath((int) $media->getKey());
        }

        if (method_exists($media, 'getUrl')) {
            return self::normalizeForEditor((string) $media->getUrl());
        }

        return null;
    }

    protected static function bindingContext(bool|BindingContext $previewContext): BindingContext
    {
        if ($previewContext instanceof BindingContext) {
            return $previewContext;
        }

        return new BindingContext(editorPreview: (bool) $previewContext);
    }

    /**
     * @return list<string>
     */
    protected static function candidateCollections(string $fieldId): array
    {
        $candidates = [
            $fieldId,
            Str::snake($fieldId),
            Str::camel($fieldId),
            'featured_image',
            'featured',
            'image',
            'cover',
            'cover_image',
            'logo',
            'thumbnail',
            'avatar',
            'author_avatar',
        ];

        return array_values(array_unique(array_filter($candidates)));
    }
}
