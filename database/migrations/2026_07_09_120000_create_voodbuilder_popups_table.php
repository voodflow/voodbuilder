<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voodbuilder_popups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->json('rules');
            $table->longText('html')->nullable();
            $table->text('css')->nullable();
            $table->text('js')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_popups');
    }
};
