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
            if (! Schema::hasColumn('site_pages', 'is_dynamic')) {
                $table->boolean('is_dynamic')->default(false)->after('is_home');
            }

            if (! Schema::hasColumn('site_pages', 'dynamic_channel')) {
                $table->string('dynamic_channel', 64)->nullable()->after('is_dynamic')->index();
            }

            if (! Schema::hasColumn('site_pages', 'dynamic_routes')) {
                $table->json('dynamic_routes')->nullable()->after('dynamic_channel');
            }

            if (! Schema::hasColumn('site_pages', 'dynamic_priority')) {
                $table->unsignedSmallInteger('dynamic_priority')->default(0)->after('dynamic_routes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_pages')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $table): void {
            foreach (['dynamic_priority', 'dynamic_routes', 'dynamic_channel', 'is_dynamic'] as $column) {
                if (Schema::hasColumn('site_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
