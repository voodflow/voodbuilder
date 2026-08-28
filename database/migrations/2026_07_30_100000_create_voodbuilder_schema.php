<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline schema for VoodBuilder (pre-production consolidation of incremental migrations).
 *
 * Idempotent: host apps may already have created `site_pages` (or other tables)
 * via older app/package migrations. Skip existing tables and upgrade `site_pages`
 * columns when the legacy minimal schema is present.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('avatar')->nullable()->after('email');
            });
        }

        if (! Schema::hasTable('voodbuilder_chrome_layouts')) {
            Schema::create('voodbuilder_chrome_layouts', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->longText('html')->nullable();
                $table->longText('css')->nullable();
                $table->longText('js')->nullable();
                $table->boolean('enabled')->default(true);
                $table->boolean('is_default')->default(false);
                $table->json('channel_ids')->nullable();
                $table->string('content_width', 32)->default('full');
                $table->string('content_max_width', 32)->nullable();
                $table->string('chrome_width', 32)->default('full');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('voodbuilder_menus')) {
            Schema::create('voodbuilder_menus', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->string('link_display')->default('text_only');
                $table->string('locale', 12)->default('en')->index();
                $table->uuid('translation_group_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['slug', 'locale'], 'voodbuilder_menus_slug_locale_unique');
            });
        }

        if (! Schema::hasTable('voodbuilder_menu_items')) {
            Schema::create('voodbuilder_menu_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('menu_id')->constrained('voodbuilder_menus')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('voodbuilder_menu_items')->cascadeOnDelete();
                $table->string('label');
                $table->string('icon', 64)->nullable();
                $table->string('type');
                $table->string('link')->nullable();
                $table->json('route_parameters')->nullable();
                $table->string('route_match')->nullable();
                $table->boolean('open_in_new_tab')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('voodbuilder_settings')) {
            Schema::create('voodbuilder_settings', function (Blueprint $table): void {
                $table->id();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('voodbuilder_media_libraries')) {
            Schema::create('voodbuilder_media_libraries', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->default('Site media');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('voodbuilder_model_integrations')) {
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

        if (! Schema::hasTable('voodbuilder_page_templates')) {
            Schema::create('voodbuilder_page_templates', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('category')->nullable();
                $table->text('description')->nullable();
                $table->longText('html');
                $table->text('css')->nullable();
                $table->text('js')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('site_pages')) {
            Schema::create('site_pages', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug');
                $table->string('locale', 12)->default('en')->index();
                $table->uuid('translation_group_id')->nullable()->index();
                $table->json('content')->nullable();
                $table->string('builder')->default('rich_editor');
                $table->json('builder_payload')->nullable();
                $table->string('layout')->default('page');
                $table->foreignUuid('chrome_layout_id')
                    ->nullable()
                    ->constrained('voodbuilder_chrome_layouts')
                    ->nullOnDelete();
                $table->boolean('hide_site_footer')->default(false);
                $table->boolean('hide_site_nav')->default(false);
                $table->boolean('hide_site_header')->default(false);
                $table->string('sub_theme')->nullable();
                $table->string('section')->nullable();
                $table->string('excerpt', 500)->nullable();
                $table->boolean('section_home')->default(false);
                $table->boolean('is_home')->default(false);
                $table->boolean('published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique(['slug', 'locale'], 'site_pages_slug_locale_unique');
            });
        } else {
            $this->upgradeLegacySitePages();
        }

        if (! Schema::hasTable('voodbuilder_site_page_revisions')) {
            Schema::create('voodbuilder_site_page_revisions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_page_id')->constrained('site_pages')->cascadeOnDelete();
                $table->json('builder_payload');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Host apps often ship a minimal `site_pages` migration before this baseline.
     */
    private function upgradeLegacySitePages(): void
    {
        Schema::table('site_pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_pages', 'locale')) {
                $table->string('locale', 12)->default('en')->after('slug')->index();
            }
            if (! Schema::hasColumn('site_pages', 'translation_group_id')) {
                $table->uuid('translation_group_id')->nullable()->after('locale')->index();
            }
            if (! Schema::hasColumn('site_pages', 'builder')) {
                $table->string('builder')->default('rich_editor')->after('content');
            }
            if (! Schema::hasColumn('site_pages', 'builder_payload')) {
                $table->json('builder_payload')->nullable()->after('builder');
            }
            if (! Schema::hasColumn('site_pages', 'chrome_layout_id')) {
                $table->foreignUuid('chrome_layout_id')
                    ->nullable()
                    ->after('layout')
                    ->constrained('voodbuilder_chrome_layouts')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('site_pages', 'hide_site_footer')) {
                $table->boolean('hide_site_footer')->default(false)->after('chrome_layout_id');
            }
            if (! Schema::hasColumn('site_pages', 'hide_site_nav')) {
                $table->boolean('hide_site_nav')->default(false)->after('hide_site_footer');
            }
            if (! Schema::hasColumn('site_pages', 'hide_site_header')) {
                $table->boolean('hide_site_header')->default(false)->after('hide_site_nav');
            }
            if (! Schema::hasColumn('site_pages', 'sub_theme')) {
                $table->string('sub_theme')->nullable()->after('hide_site_header');
            }
            if (! Schema::hasColumn('site_pages', 'section')) {
                $table->string('section')->nullable()->after('sub_theme');
            }
            if (! Schema::hasColumn('site_pages', 'excerpt')) {
                $table->string('excerpt', 500)->nullable()->after('section');
            }
            if (! Schema::hasColumn('site_pages', 'section_home')) {
                $table->boolean('section_home')->default(false)->after('excerpt');
            }
        });

        // Prefer composite uniqueness (slug + locale) over legacy unique(slug).
        try {
            Schema::table('site_pages', function (Blueprint $table): void {
                $table->dropUnique(['slug']);
            });
        } catch (Throwable) {
            // Index may already be gone or named differently.
        }

        try {
            Schema::table('site_pages', function (Blueprint $table): void {
                $table->unique(['slug', 'locale'], 'site_pages_slug_locale_unique');
            });
        } catch (Throwable) {
            // Composite unique may already exist.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voodbuilder_site_page_revisions');
        // Do not drop site_pages on hosts that own it via an app migration.
        Schema::dropIfExists('voodbuilder_page_templates');
        Schema::dropIfExists('voodbuilder_model_integrations');
        Schema::dropIfExists('voodbuilder_media_libraries');
        Schema::dropIfExists('voodbuilder_settings');
        Schema::dropIfExists('voodbuilder_menu_items');
        Schema::dropIfExists('voodbuilder_menus');
        Schema::dropIfExists('voodbuilder_chrome_layouts');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('avatar');
            });
        }
    }
};
