<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_pages') || ! Schema::hasColumn('site_pages', 'builder')) {
            return;
        }

        DB::table('site_pages')
            ->where('builder', 'grapesjs')
            ->update(['builder' => 'visual']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_pages') || ! Schema::hasColumn('site_pages', 'builder')) {
            return;
        }

        DB::table('site_pages')
            ->where('builder', 'visual')
            ->update(['builder' => 'grapesjs']);
    }
};
