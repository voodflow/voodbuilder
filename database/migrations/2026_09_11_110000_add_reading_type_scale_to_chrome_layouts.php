<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voodbuilder_chrome_layouts')) {
            return;
        }

        Schema::table('voodbuilder_chrome_layouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_sidebar_font')) {
                $table->string('reading_sidebar_font', 120)->nullable()->after('reading_font_size');
            }

            if (! Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_type_scale')) {
                $table->json('reading_type_scale')->nullable()->after('reading_sidebar_font');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('voodbuilder_chrome_layouts')) {
            return;
        }

        Schema::table('voodbuilder_chrome_layouts', function (Blueprint $table): void {
            if (Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_type_scale')) {
                $table->dropColumn('reading_type_scale');
            }

            if (Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_sidebar_font')) {
                $table->dropColumn('reading_sidebar_font');
            }
        });
    }
};
