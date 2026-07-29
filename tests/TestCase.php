<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\CookieConsent\Settings\CookieConsentSettings;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use RalphJSmit\Laravel\SEO\LaravelSEOServiceProvider;
use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;
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
use Voodflow\VoodbuilderPopups\VoodbuilderPopups;
use Voodflow\VoodbuilderPopups\VoodbuilderPopupsServiceProvider;
use Voodflow\VoodbuilderTemplates\VoodbuilderTemplates;
use Voodflow\VoodbuilderTemplates\VoodbuilderTemplatesServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return array_values(array_filter([
            LivewireServiceProvider::class,
            LaravelSEOServiceProvider::class,
            VoodbuilderServiceProvider::class,
            class_exists(VoodbuilderPopupsServiceProvider::class)
                ? VoodbuilderPopupsServiceProvider::class
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

        $app['config']->set('settings', [
            'settings' => [
                CookieConsentSettings::class,
            ],
            'default_repository' => 'database',
            'repositories' => [
                'database' => [
                    'type' => DatabaseSettingsRepository::class,
                    'model' => null,
                    'table' => 'settings',
                    'connection' => null,
                ],
            ],
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Filament panel plugins are not registered in Testbench; activate companion runtimes for package tests.
        if (class_exists(VoodbuilderPopups::class)) {
            EditorGate::flushLabelProviders();
            VoodbuilderPopups::reset();
            VoodbuilderPopups::activate();
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

        $this->seedCookieConsentSettings();
    }

    protected function tearDown(): void
    {
        PageBuilderAccess::authorizeUsing(null);

        NavigationMenuResolver::clearSchemaCache();
        SitePageResolver::clearSchemaCache();

        parent::tearDown();
    }

    protected function seedCookieConsentSettings(): void
    {
        if (! class_exists(CookieConsentSettings::class)) {
            return;
        }

        $defaults = [
            'css_url' => 'https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css',
            'js_url' => 'https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js',
            'content_header' => 'Cookies used on the website!',
            'content_message' => 'This website uses cookies to ensure you get the best experience on our website.',
            'content_dismiss' => 'Got it!',
            'content_allow' => 'Allow cookies',
            'content_deny' => 'Decline',
            'content_link' => 'Learn more',
            'content_href' => null,
            'content_close' => '&#x274c;',
            'content_target' => '_blank',
            'content_policy' => 'Cookie Policy',
            'popup_background' => '#696969',
            'popup_text' => '#FFFFFF',
            'popup_link' => '#FFFFFF',
            'button_background' => 'transparent',
            'button_border' => '#f8e71c',
            'button_text' => '#f8e71c',
            'highlight_background' => '#f8e71c',
            'highlight_border' => '#f8e71c',
            'highlight_text' => '#000000',
            'position' => 'bottom-left',
            'theme' => 'block',
        ];

        $now = now();

        foreach ($defaults as $name => $value) {
            DB::table('settings')->insert([
                'group' => 'cookie_consent',
                'name' => $name,
                'locked' => false,
                'payload' => json_encode($value, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
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

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
