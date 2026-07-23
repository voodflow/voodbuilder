<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voodbuilder_popup_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('popup_id')
                ->constrained('voodbuilder_popups')
                ->cascadeOnDelete();
            $table->string('event', 32);
            $table->string('close_reason', 32)->nullable();
            $table->string('page_path', 255)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['popup_id', 'event', 'occurred_at']);
            $table->index(['popup_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_popup_events');
    }
};
