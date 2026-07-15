<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_pages') || Schema::hasColumn('site_pages', 'chrome_layout_id')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $table): void {
            $table->foreignUuid('chrome_layout_id')
                ->nullable()
                ->after('layout')
                ->constrained('voodbuilder_chrome_layouts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_pages') || ! Schema::hasColumn('site_pages', 'chrome_layout_id')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('chrome_layout_id');
        });
    }
};
