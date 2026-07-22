<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voodbuilder_chrome_layouts', function (Blueprint $table): void {
            $table->string('chrome_width', 32)->default('full')->after('content_max_width');
        });
    }

    public function down(): void
    {
        Schema::table('voodbuilder_chrome_layouts', function (Blueprint $table): void {
            $table->dropColumn('chrome_width');
        });
    }
};
