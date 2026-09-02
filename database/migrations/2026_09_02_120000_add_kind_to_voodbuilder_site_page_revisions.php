<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separate autosaves from the revisions an author deliberately created.
 *
 * Autosaves share the storage and restore path of manual revisions, but not their budget:
 * pruning keeps 50 rows per page, so unattended saves every couple of minutes would evict
 * every real save point within an afternoon. The column lets the two be counted, pruned
 * and listed independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voodbuilder_site_page_revisions')) {
            return;
        }

        Schema::table('voodbuilder_site_page_revisions', function (Blueprint $table): void {
            if (! Schema::hasColumn('voodbuilder_site_page_revisions', 'kind')) {
                // Existing rows are all deliberate saves, and the default keeps any
                // third-party writer that predates this column on the manual budget.
                $table->string('kind', 16)->default('manual')->after('site_page_id');
            }
        });

        Schema::table('voodbuilder_site_page_revisions', function (Blueprint $table): void {
            // Pruning and the recovery lookup both filter by page + kind and order by id.
            $table->index(['site_page_id', 'kind', 'id'], 'vb_page_revisions_page_kind_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('voodbuilder_site_page_revisions')) {
            return;
        }

        Schema::table('voodbuilder_site_page_revisions', function (Blueprint $table): void {
            $table->dropIndex('vb_page_revisions_page_kind_idx');
        });

        Schema::table('voodbuilder_site_page_revisions', function (Blueprint $table): void {
            if (Schema::hasColumn('voodbuilder_site_page_revisions', 'kind')) {
                $table->dropColumn('kind');
            }
        });
    }
};
