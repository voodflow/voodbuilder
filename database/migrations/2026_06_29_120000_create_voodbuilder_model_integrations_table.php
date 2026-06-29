<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voodbuilder_model_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('model_class')->unique();
            $table->string('model_alias')->nullable();
            $table->json('fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_model_integrations');
    }
};
