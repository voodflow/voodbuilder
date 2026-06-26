<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_pages')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $blueprint): void {
            if (! Schema::hasColumn('site_pages', 'locale')) {
                $blueprint->string('locale', 12)->default('en')->after('slug');
                $blueprint->index('locale');
            }

            if (! Schema::hasColumn('site_pages', 'translation_group_id')) {
                $blueprint->uuid('translation_group_id')->nullable()->after('locale');
                $blueprint->index('translation_group_id');
            }
        });

        $defaultLocale = (string) config('vtuts.default_locale', 'en');

        DB::table('site_pages')
            ->whereNull('locale')
            ->orWhere('locale', '')
            ->update(['locale' => $defaultLocale]);

        DB::table('site_pages')
            ->whereNull('translation_group_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $row): void {
                DB::table('site_pages')
                    ->where('id', $row->id)
                    ->update(['translation_group_id' => (string) Str::uuid()]);
            });

        Schema::table('site_pages', function (Blueprint $blueprint): void {
            $indexes = collect(Schema::getIndexes('site_pages'))
                ->pluck('name')
                ->all();

            if (in_array('site_pages_slug_locale_unique', $indexes, true)) {
                return;
            }

            if (in_array('site_pages_slug_unique', $indexes, true)) {
                $blueprint->dropUnique('site_pages_slug_unique');
            } elseif (in_array('site_pages.slug', $indexes, true)) {
                $blueprint->dropUnique('site_pages.slug');
            } else {
                foreach ($indexes as $indexName) {
                    if (! str_contains($indexName, 'slug') || str_contains($indexName, 'locale')) {
                        continue;
                    }

                    $blueprint->dropUnique($indexName);

                    break;
                }
            }

            $blueprint->unique(['slug', 'locale'], 'site_pages_slug_locale_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_pages')) {
            return;
        }

        Schema::table('site_pages', function (Blueprint $blueprint): void {
            $indexes = collect(Schema::getIndexes('site_pages'))
                ->pluck('name')
                ->all();

            if (in_array('site_pages_slug_locale_unique', $indexes, true)) {
                $blueprint->dropUnique('site_pages_slug_locale_unique');
            }

            if (! in_array('site_pages_slug_unique', $indexes, true)) {
                $blueprint->unique('slug', 'site_pages_slug_unique');
            }

            if (Schema::hasColumn('site_pages', 'translation_group_id')) {
                $blueprint->dropIndex(['translation_group_id']);
                $blueprint->dropColumn('translation_group_id');
            }

            if (Schema::hasColumn('site_pages', 'locale')) {
                $blueprint->dropIndex(['locale']);
                $blueprint->dropColumn('locale');
            }
        });
    }
};
