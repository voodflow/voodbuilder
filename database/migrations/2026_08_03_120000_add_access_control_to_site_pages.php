<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_pages')) {
            Schema::table('site_pages', function (Blueprint $table): void {
                if (! Schema::hasColumn('site_pages', 'visibility')) {
                    $table->string('visibility', 32)->default('public')->after('published_at');
                }
                if (! Schema::hasColumn('site_pages', 'password_protected')) {
                    $table->boolean('password_protected')->default(false)->after('visibility');
                }
            });
        }

        if (! Schema::hasTable('site_page_credentials')) {
            Schema::create('site_page_credentials', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_page_id')->constrained('site_pages')->cascadeOnDelete();
                $table->string('email')->nullable();
                $table->string('password');
                $table->timestamps();

                $table->index(['site_page_id', 'email']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_page_credentials');

        if (Schema::hasTable('site_pages')) {
            Schema::table('site_pages', function (Blueprint $table): void {
                if (Schema::hasColumn('site_pages', 'password_protected')) {
                    $table->dropColumn('password_protected');
                }
                if (Schema::hasColumn('site_pages', 'visibility')) {
                    $table->dropColumn('visibility');
                }
            });
        }
    }
};
