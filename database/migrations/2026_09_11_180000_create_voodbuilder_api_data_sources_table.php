<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voodbuilder_api_data_sources')) {
            return;
        }

        Schema::create('voodbuilder_api_data_sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver'); // http | static
            $table->boolean('enabled')->default(true);
            /**
             * Driver-specific config (URL, headers, paths, static rows…).
             * Prefer app-level encryption for long-lived secrets in headers.
             */
            $table->json('config');
            $table->text('description')->nullable();
            $table->unsignedInteger('cache_ttl_seconds')->default(60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_api_data_sources');
    }
};
