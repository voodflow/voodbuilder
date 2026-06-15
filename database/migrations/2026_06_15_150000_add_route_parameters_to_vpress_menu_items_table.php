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
            if (! Schema::hasColumn('vpress_menu_items', 'route_parameters')) {
                $table->json('route_parameters')->nullable()->after('link');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vpress_menu_items')) {
            return;
        }

        Schema::table('vpress_menu_items', function (Blueprint $table): void {
            if (Schema::hasColumn('vpress_menu_items', 'route_parameters')) {
                $table->dropColumn('route_parameters');
            }
        });
    }
};
