<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Singleton media owner for reusable site images and videos (Spatie Media Library).
 */
class MediaLibrary extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const COLLECTION_IMAGES = 'images';

    public const COLLECTION_VIDEOS = 'videos';

    protected $table = 'voodbuilder_media_libraries';

    protected $fillable = [
        'name',
    ];

    public static function current(): self
    {
        $existing = static::query()->orderBy('id')->first();

        if ($existing !== null) {
            return $existing;
        }

        return static::query()->create([
            'name' => 'Site media',
        ]);
    }

    public function registerMediaCollections(): void
    {
        $disk = (string) config('voodbuilder.editor.upload.disk', 'public');

        $this->addMediaCollection(self::COLLECTION_IMAGES)
            ->useDisk($disk);

        $this->addMediaCollection(self::COLLECTION_VIDEOS)
            ->useDisk($disk);
    }
}
