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
            $table->string('content_width', 32)->default('full')->after('channel_ids');
            $table->string('content_max_width', 32)->nullable()->after('content_width');
        });
    }

    public function down(): void
    {
        Schema::table('voodbuilder_chrome_layouts', function (Blueprint $table): void {
            $table->dropColumn(['content_width', 'content_max_width']);
        });
    }
};
