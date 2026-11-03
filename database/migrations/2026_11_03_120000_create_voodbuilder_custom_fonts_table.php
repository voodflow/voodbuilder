<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voodbuilder_custom_fonts')) {
            return;
        }

        Schema::create('voodbuilder_custom_fonts', function (Blueprint $table): void {
            $table->id();
            $table->string('family');
            $table->string('slug')->unique();
            $table->string('category', 32)->default('sans-serif');
            $table->string('stack');
            $table->json('weights')->nullable();
            $table->json('files')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_custom_fonts');
    }
};
