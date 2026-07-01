<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voodbuilder_components', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->longText('html');
            $table->text('css')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_components');
    }
};
