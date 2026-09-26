<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Page templates now persist the full published sheet (author + live utilities)
 * so apply can skip Node JIT. TEXT (64 KiB) truncates large product pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voodbuilder_page_templates')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE voodbuilder_page_templates MODIFY css LONGTEXT NULL');
            DB::statement('ALTER TABLE voodbuilder_page_templates MODIFY js LONGTEXT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE voodbuilder_page_templates ALTER COLUMN css TYPE TEXT');
            DB::statement('ALTER TABLE voodbuilder_page_templates ALTER COLUMN js TYPE TEXT');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('voodbuilder_page_templates')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        // Shrinking LONGTEXT → TEXT can fail if rows already exceed 64 KiB; skip safely.
        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                DB::statement('ALTER TABLE voodbuilder_page_templates MODIFY css TEXT NULL');
                DB::statement('ALTER TABLE voodbuilder_page_templates MODIFY js TEXT NULL');
            } catch (\Throwable) {
                // Keep LONGTEXT when data no longer fits TEXT.
            }
        }
    }
};
