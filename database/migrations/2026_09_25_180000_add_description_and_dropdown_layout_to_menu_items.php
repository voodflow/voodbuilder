<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voodbuilder_menu_items')) {
            return;
        }

        Schema::table('voodbuilder_menu_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('voodbuilder_menu_items', 'description')) {
                $table->string('description', 255)->nullable()->after('label');
            }

            if (! Schema::hasColumn('voodbuilder_menu_items', 'dropdown_layout')) {
                $table->string('dropdown_layout', 16)->nullable()->after('icon');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('voodbuilder_menu_items')) {
            return;
        }

        Schema::table('voodbuilder_menu_items', function (Blueprint $table): void {
            if (Schema::hasColumn('voodbuilder_menu_items', 'dropdown_layout')) {
                $table->dropColumn('dropdown_layout');
            }

            if (Schema::hasColumn('voodbuilder_menu_items', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
