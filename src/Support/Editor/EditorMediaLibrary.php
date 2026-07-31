<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Voodflow\Voodbuilder\Models\MediaLibrary;
use Voodflow\Voodbuilder\Support\PublicDiskUrl;

/**
 * Reusable media library helpers for the Editor AssetManager (Spatie-backed).
 */
final class EditorMediaLibrary
{
    /**
     * @return list<array{src: string, type: string, name: string, uuid: string, id: int}>
     */
    public static function listAssets(?string $type = null): array
    {
        $library = MediaLibrary::current();
        $assets = [];

        $collections = match ($type) {
            'image' => [MediaLibrary::COLLECTION_IMAGES],
            'video' => [MediaLibrary::COLLECTION_VIDEOS],
            default => [MediaLibrary::COLLECTION_IMAGES, MediaLibrary::COLLECTION_VIDEOS],
        };

        foreach ($collections as $collection) {
            $kind = $collection === MediaLibrary::COLLECTION_VIDEOS ? 'video' : 'image';

            foreach ($library->getMedia($collection) as $media) {
                $assets[] = self::toAssetPayload($media, $kind);
            }
        }

        usort(
            $assets,
            static fn (array $left, array $right): int => ($right['id'] ?? 0) <=> ($left['id'] ?? 0),
        );

        return $assets;
    }

    public static function store(UploadedFile $file): Media
    {
        $mime = (string) ($file->getMimeType() ?? '');
        $isVideo = str_starts_with($mime, 'video/');
        $collection = $isVideo ? MediaLibrary::COLLECTION_VIDEOS : MediaLibrary::COLLECTION_IMAGES;

        return MediaLibrary::current()
            ->addMedia($file)
            ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: $file->hashName())
            ->usingFileName($file->hashName())
            ->toMediaCollection($collection);
    }

    /**
     * @return array{src: string, type: string, name: string, uuid: string, id: int}
     */
    public static function toAssetPayload(Media $media, ?string $type = null): array
    {
        $resolvedType = $type
            ?? (str_starts_with((string) $media->mime_type, 'video/') ? 'video' : 'image');

        return [
            'src' => self::publicUrl($media),
            'type' => $resolvedType,
            'name' => (string) ($media->name ?: $media->file_name),
            'uuid' => (string) $media->uuid,
            'id' => (int) $media->getKey(),
        ];
    }

    public static function publicUrl(Media $media): string
    {
        return PublicDiskUrl::fromPath((string) $media->getPathRelativeToRoot());
    }
}