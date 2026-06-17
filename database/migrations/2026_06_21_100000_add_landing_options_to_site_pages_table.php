<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_pages')) {
            return;
        }

        if (Schema::hasColumn('site_pages', 'hide_site_footer')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $table): void {
            $table->boolean('hide_site_footer')->default(false)->after('layout');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_pages') || ! Schema::hasColumn('site_pages', 'hide_site_footer')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $table): void {
            $table->dropColumn('hide_site_footer');
        });
    }
};
