<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voodbuilder_components', function (Blueprint $table): void {
            $table->string('html_checksum', 64)->nullable()->after('css');
        });

        if (! Schema::hasTable('voodbuilder_components')) {
            return;
        }

        DB::table('voodbuilder_components')
            ->select(['id', 'html'])
            ->orderBy('id')
            ->chunkById(50, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('voodbuilder_components')
                        ->where('id', $row->id)
                        ->update([
                            'html_checksum' => GrapesJsPastedComponentNormalizer::htmlChecksum((string) $row->html),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('voodbuilder_components', function (Blueprint $table): void {
            $table->dropColumn('html_checksum');
        });
    }
};
