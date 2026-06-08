<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use RalphJSmit\Laravel\SEO\LaravelSEOServiceProvider;
use Voodflow\Vpress\VpressServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            LaravelSEOServiceProvider::class,
            VpressServiceProvider::class,
        ];
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

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('vpress.pages.enabled', true);
        $app['config']->set('vpress.pages.prefix', 'pages');
        $app['config']->set('vpress.home.route_enabled', false);
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

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
