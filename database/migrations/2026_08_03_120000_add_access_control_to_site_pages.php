<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_pages', function (Blueprint $table): void {
            $table->string('visibility', 32)->default('public')->after('published_at');
            $table->boolean('password_protected')->default(false)->after('visibility');
        });

        Schema::create('site_page_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_page_id')->constrained('site_pages')->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('password');
            $table->timestamps();

            $table->index(['site_page_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_page_credentials');

        Schema::table('site_pages', function (Blueprint $table): void {
            $table->dropColumn(['visibility', 'password_protected']);
        });
    }
};
