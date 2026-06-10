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

        Schema::table('site_pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_pages', 'section')) {
                $table->string('section')->nullable()->after('sub_theme');
            }

            if (! Schema::hasColumn('site_pages', 'excerpt')) {
                $table->string('excerpt', 500)->nullable()->after('section');
            }

            if (! Schema::hasColumn('site_pages', 'section_home')) {
                $table->boolean('section_home')->default(false)->after('excerpt');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_pages')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $table): void {
            foreach (['section', 'excerpt', 'section_home'] as $column) {
                if (Schema::hasColumn('site_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
