<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use RalphJSmit\Laravel\SEO\LaravelSEOServiceProvider;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\NavigationMenuResolver;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\VoodbuilderServiceProvider;
use Voodflow\VoodbuilderComponents\VoodbuilderComponents;
use Voodflow\VoodbuilderComponents\VoodbuilderComponentsServiceProvider;
use Voodflow\VoodbuilderDynamicData\VoodbuilderDynamicData;
use Voodflow\VoodbuilderDynamicData\VoodbuilderDynamicDataServiceProvider;
use Voodflow\VoodbuilderTemplates\VoodbuilderTemplates;
use Voodflow\VoodbuilderTemplates\VoodbuilderTemplatesServiceProvider;
use Voodflow\Vpopups\Vpopups;
use Voodflow\Vpopups\VpopupsServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return array_values(array_filter([
            LivewireServiceProvider::class,
            LaravelSEOServiceProvider::class,
            VoodbuilderServiceProvider::class,
            class_exists(VpopupsServiceProvider::class)
                ? VpopupsServiceProvider::class
                : null,
            class_exists(VoodbuilderComponentsServiceProvider::class)
                ? VoodbuilderComponentsServiceProvider::class
                : null,
            class_exists(VoodbuilderDynamicDataServiceProvider::class)
                ? VoodbuilderDynamicDataServiceProvider::class
                : null,
            class_exists(VoodbuilderTemplatesServiceProvider::class)
                ? VoodbuilderTemplatesServiceProvider::class
                : null,
        ]));
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('cache.default', 'array');

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('voodbuilder.pages.enabled', true);
        $app['config']->set('voodbuilder.pages.prefix', 'pages');
        $app['config']->set('voodbuilder.home.route_enabled', false);
        // Package still ships Agency surfaces in-repo; tests use Agency until proprietary splits exist.
        $app['config']->set('voodbuilder.license.edition', 'agency');
        $app['config']->set('voodbuilder.license.cache', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Filament panel plugins are not registered in Testbench; activate companion runtimes for package tests.
        if (class_exists(Vpopups::class)) {
            EditorGate::flushLabelProviders();
            Vpopups::reset();
            Vpopups::activate();
        }

        if (class_exists(VoodbuilderComponents::class)) {
            VoodbuilderComponents::reset();
            VoodbuilderComponents::activate();
        }

        if (class_exists(VoodbuilderDynamicData::class)) {
            VoodbuilderDynamicData::reset();
            VoodbuilderDynamicData::activate();
        }

        if (class_exists(VoodbuilderTemplates::class)) {
            VoodbuilderTemplates::reset();
            VoodbuilderTemplates::activate();
        }

        NavigationMenuResolver::clearSchemaCache();
        SitePageResolver::clearSchemaCache();
        ChromeLayoutResolver::forgetCache();
    }

    protected function tearDown(): void
    {
        PageBuilderAccess::authorizeUsing(null);

        NavigationMenuResolver::clearSchemaCache();
        SitePageResolver::clearSchemaCache();

        parent::tearDown();
    }

    protected function defineDatabaseMigrations(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        $schema->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        $schema->create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $schema->create('seo', function (Blueprint $table): void {
            $table->id();
            $table->morphs('model');
            $table->longText('description')->nullable();
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('author')->nullable();
            $table->string('robots')->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
        });

        $schema->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['group', 'name']);
        });

        $schema->create('media', function (Blueprint $table): void {
            $table->id();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}