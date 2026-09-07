<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Support\PluginLayout;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class PluginLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/docs-test', fn () => 'ok')->name('docs.test');
    }

    public function test_app_shell_uses_classic_layout_without_chrome(): void
    {
        $this->assertSame('voodbuilder::layouts.app', PluginLayout::appShell());
        $this->assertFalse(PluginLayout::usesChromeShell());
    }

    public function test_app_shell_uses_chrome_layout_when_channel_matches(): void
    {
        Voodbuilder::contentChannel('docs', [
            'label' => 'Docs',
            'routes' => ['docs.test'],
        ]);

        ChromeLayout::query()->create([
            'name' => 'Docs chrome',
            'slug' => 'docs-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'channel_ids' => ['docs'],
        ]);

        ChromeLayoutResolver::forgetCache();

        $this->get('/docs-test');

        $this->assertSame('voodbuilder::layouts.chrome-app', PluginLayout::appShell());
        $this->assertTrue(PluginLayout::usesChromeShell());
        $this->assertInstanceOf(ChromeLayout::class, ChromeLayoutResolver::activeLayout());
    }

    public function test_resolver_cache_stores_layout_id_not_model_instance(): void
    {
        Voodbuilder::contentChannel('docs', [
            'label' => 'Docs',
            'routes' => ['docs.test'],
        ]);

        $layout = ChromeLayout::query()->create([
            'name' => 'Cached chrome',
            'slug' => 'cached-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'channel_ids' => ['docs'],
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel('docs');

        $this->assertNotNull($resolved);
        $this->assertSame($layout->id, $resolved->id);

        $cached = Cache::get('voodbuilder.chrome_layout_id.docs');

        $this->assertIsString($cached);
        $this->assertSame($layout->id, $cached);
    }
}
