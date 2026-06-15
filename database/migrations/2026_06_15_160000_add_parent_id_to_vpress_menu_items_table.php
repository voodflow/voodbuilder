<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vpress_menu_items')) {
            return;
        }

        Schema::table('vpress_menu_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('vpress_menu_items', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('menu_id')
                    ->constrained('vpress_menu_items')
                    ->cascadeOnDelete();
            }
        });

        if (Schema::hasColumn('vpress_menu_items', 'link')) {
            Schema::table('vpress_menu_items', function (Blueprint $table): void {
                $table->string('link')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vpress_menu_items')) {
            return;
        }

        Schema::table('vpress_menu_items', function (Blueprint $table): void {
            if (Schema::hasColumn('vpress_menu_items', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });
    }
};
