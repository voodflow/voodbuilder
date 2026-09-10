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
            if (! Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_font')) {
                $table->string('reading_font', 120)->nullable()->after('chrome_width');
            }

            if (! Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_font_size')) {
                $table->string('reading_font_size', 16)->nullable()->after('reading_font');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('voodbuilder_chrome_layouts')) {
            return;
        }

        Schema::table('voodbuilder_chrome_layouts', function (Blueprint $table): void {
            if (Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_font_size')) {
                $table->dropColumn('reading_font_size');
            }

            if (Schema::hasColumn('voodbuilder_chrome_layouts', 'reading_font')) {
                $table->dropColumn('reading_font');
            }
        });
    }
};
