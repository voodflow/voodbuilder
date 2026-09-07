<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retire this package's own media library; voodflow/vmedia owns media now.
 *
 * The old library was a singleton owner row with Spatie media hanging off it, listed in the
 * admin under a label identical to vmedia's — two libraries over different tables, where
 * uploading to the wrong one meant the editor could not see the file.
 */
return new class extends Migration
{
    private const MORPH_ALIAS = 'voodbuilder_media_library';

    public function up(): void
    {
        $this->detachLegacyMediaRows();

        Schema::dropIfExists('voodbuilder_media_libraries');
    }

    /**
     * Drop the rows, keep the files.
     *
     * Deleted through Eloquent, Spatie would take the files on disk with them — and those
     * files are still referenced by URL in the HTML of already published pages, which would
     * turn a cleanup into broken images on live sites. A raw delete skips the model events,
     * so the bytes stay where the pages expect them. Anything genuinely unreferenced is what
     * `vmedia:prune-orphans` is for.
     */
    private function detachLegacyMediaRows(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        DB::table('media')
            ->where('model_type', self::MORPH_ALIAS)
            ->delete();
    }

    public function down(): void
    {
        // Recreates the owner table so a rollback can boot, but not the media rows: their
        // owner ids are gone and inventing new ones would attach files to the wrong record.
        if (Schema::hasTable('voodbuilder_media_libraries')) {
            return;
        }

        Schema::create('voodbuilder_media_libraries', function ($table): void {
            $table->id();
            $table->string('name')->default('Site media');
            $table->timestamps();
        });
    }
};
