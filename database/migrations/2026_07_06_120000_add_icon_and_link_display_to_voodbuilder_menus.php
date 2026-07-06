<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voodbuilder_menus') && ! Schema::hasColumn('voodbuilder_menus', 'link_display')) {
            Schema::table('voodbuilder_menus', function (Blueprint $table): void {
                $table->string('link_display', 32)->default('text_only')->after('slug');
            });
        }

        if (Schema::hasTable('voodbuilder_menu_items') && ! Schema::hasColumn('voodbuilder_menu_items', 'icon')) {
            Schema::table('voodbuilder_menu_items', function (Blueprint $table): void {
                $table->string('icon', 64)->nullable()->after('label');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('voodbuilder_menus') && Schema::hasColumn('voodbuilder_menus', 'link_display')) {
            Schema::table('voodbuilder_menus', function (Blueprint $table): void {
                $table->dropColumn('link_display');
            });
        }

        if (Schema::hasTable('voodbuilder_menu_items') && Schema::hasColumn('voodbuilder_menu_items', 'icon')) {
            Schema::table('voodbuilder_menu_items', function (Blueprint $table): void {
                $table->dropColumn('icon');
            });
        }
    }
};
