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
        if (! Schema::hasTable('voodbuilder_menus')) {
            return;
        }

        Schema::table('voodbuilder_menus', function (Blueprint $blueprint): void {
            if (! Schema::hasColumn('voodbuilder_menus', 'locale')) {
                $blueprint->string('locale', 12)->default('en')->after('slug');
                $blueprint->index('locale');
            }

            if (! Schema::hasColumn('voodbuilder_menus', 'translation_group_id')) {
                $blueprint->uuid('translation_group_id')->nullable()->after('locale');
                $blueprint->index('translation_group_id');
            }
        });

        $defaultLocale = (string) config('vtuts.default_locale', 'en');

        DB::table('voodbuilder_menus')
            ->whereNull('locale')
            ->orWhere('locale', '')
            ->update(['locale' => $defaultLocale]);

        DB::table('voodbuilder_menus')
            ->whereNull('translation_group_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $row): void {
                DB::table('voodbuilder_menus')
                    ->where('id', $row->id)
                    ->update(['translation_group_id' => (string) Str::uuid()]);
            });

        Schema::table('voodbuilder_menus', function (Blueprint $blueprint): void {
            $indexes = collect(Schema::getIndexes('voodbuilder_menus'))
                ->pluck('name')
                ->all();

            if (in_array('voodbuilder_menus_slug_locale_unique', $indexes, true)) {
                return;
            }

            if (in_array('voodbuilder_menus_slug_unique', $indexes, true)) {
                $blueprint->dropUnique('voodbuilder_menus_slug_unique');
            } else {
                foreach ($indexes as $indexName) {
                    if (! str_contains($indexName, 'slug') || str_contains($indexName, 'locale')) {
                        continue;
                    }

                    $blueprint->dropUnique($indexName);

                    break;
                }
            }

            $blueprint->unique(['slug', 'locale'], 'voodbuilder_menus_slug_locale_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('voodbuilder_menus')) {
            return;
        }

        Schema::table('voodbuilder_menus', function (Blueprint $blueprint): void {
            $indexes = collect(Schema::getIndexes('voodbuilder_menus'))
                ->pluck('name')
                ->all();

            if (in_array('voodbuilder_menus_slug_locale_unique', $indexes, true)) {
                $blueprint->dropUnique('voodbuilder_menus_slug_locale_unique');
            }

            if (! in_array('voodbuilder_menus_slug_unique', $indexes, true)) {
                $blueprint->unique('slug', 'voodbuilder_menus_slug_unique');
            }

            if (Schema::hasColumn('voodbuilder_menus', 'translation_group_id')) {
                $blueprint->dropIndex(['translation_group_id']);
                $blueprint->dropColumn('translation_group_id');
            }

            if (Schema::hasColumn('voodbuilder_menus', 'locale')) {
                $blueprint->dropIndex(['locale']);
                $blueprint->dropColumn('locale');
            }
        });
    }
};
