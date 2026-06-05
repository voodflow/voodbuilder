<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vpress_menus')) {
            Schema::create('vpress_menus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vpress_menu_items')) {
            Schema::create('vpress_menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_id')->constrained('vpress_menus')->cascadeOnDelete();
                $table->string('label');
                $table->string('type');
                $table->string('link');
                $table->string('route_match')->nullable();
                $table->boolean('open_in_new_tab')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vpress_settings')) {
            Schema::create('vpress_settings', function (Blueprint $table) {
                $table->id();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vpress_menu_items');
        Schema::dropIfExists('vpress_menus');
        Schema::dropIfExists('vpress_settings');
    }
};
