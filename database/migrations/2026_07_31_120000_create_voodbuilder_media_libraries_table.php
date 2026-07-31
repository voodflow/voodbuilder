<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media library singleton table (for hosts that already ran the baseline schema).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voodbuilder_media_libraries')) {
            return;
        }

        Schema::create('voodbuilder_media_libraries', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Site media');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_media_libraries');
    }
};
