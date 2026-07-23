<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voodbuilder_popups', function (Blueprint $table): void {
            $table->boolean('paused')->default(false)->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('voodbuilder_popups', function (Blueprint $table): void {
            $table->dropColumn('paused');
        });
    }
};
