<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voodbuilder_menus')) {
            Schema::create('voodbuilder_menus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('voodbuilder_menu_items')) {
            Schema::create('voodbuilder_menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_id')->constrained('voodbuilder_menus')->cascadeOnDelete();
                $table->string('label');
                $table->string('type');
                $table->string('link');
                $table->string('route_match')->nullable();
                $table->boolean('open_in_new_tab')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('voodbuilder_settings')) {
            Schema::create('voodbuilder_settings', function (Blueprint $table) {
                $table->id();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_menu_items');
        Schema::dropIfExists('voodbuilder_menus');
        Schema::dropIfExists('voodbuilder_settings');
    }
};
