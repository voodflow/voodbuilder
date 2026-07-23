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
            $table->text('description')->nullable()->after('name');
            $table->string('locale', 12)->nullable()->after('description')->index();
        });
    }

    public function down(): void
    {
        Schema::table('voodbuilder_popups', function (Blueprint $table): void {
            $table->dropColumn(['description', 'locale']);
        });
    }
};
