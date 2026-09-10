<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutResolverTest extends TestCase
{
    public function test_channel_specific_layout_beats_default_even_when_default_lists_same_channel(): void
    {
        $default = ChromeLayout::query()->create([
            'name' => 'Site default',
            'slug' => 'site-default',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            // Misconfiguration / legacy: default also lists docs — must still lose.
            'channel_ids' => ['docs', 'pages', 'tutorials'],
        ]);

        $docs = ChromeLayout::query()->create([
            'name' => 'Docs chrome',
            'slug' => 'docs-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => false,
            'channel_ids' => ['docs'],
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel('docs');

        $this->assertNotNull($resolved);
        $this->assertSame($docs->id, $resolved->id);
        $this->assertNotSame($default->id, $resolved->id);
    }

    public function test_default_layout_used_when_channel_has_no_specific_assignment(): void
    {
        $default = ChromeLayout::query()->create([
            'name' => 'Site default',
            'slug' => 'site-default',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            'channel_ids' => [],
        ]);

        ChromeLayout::query()->create([
            'name' => 'Docs chrome',
            'slug' => 'docs-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => false,
            'channel_ids' => ['docs'],
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel('blog');

        $this->assertNotNull($resolved);
        $this->assertSame($default->id, $resolved->id);
    }

    public function test_default_layout_used_when_channel_id_is_null(): void
    {
        $default = ChromeLayout::query()->create([
            'name' => 'Site default',
            'slug' => 'site-default',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            'channel_ids' => [],
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel(null);

        $this->assertNotNull($resolved);
        $this->assertSame($default->id, $resolved->id);
    }

    public function test_ensure_single_default_clears_previous_default(): void
    {
        $first = ChromeLayout::query()->create([
            'name' => 'First default',
            'slug' => 'first-default',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            'channel_ids' => [],
        ]);

        $second = ChromeLayout::query()->create([
            'name' => 'Second default',
            'slug' => 'second-default',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            'channel_ids' => [],
        ]);

        ChromeLayout::ensureSingleDefault($second);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_tutorials_inherits_docs_layout_via_peer_channel(): void
    {
        ChromeLayout::query()->create([
            'name' => 'Site default',
            'slug' => 'site-default',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            'channel_ids' => [],
            'content_width' => 'full',
        ]);

        $docs = ChromeLayout::query()->create([
            'name' => 'Docs chrome',
            'slug' => 'docs-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => false,
            'channel_ids' => ['docs'],
            'content_width' => 'standard',
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel('tutorials');

        $this->assertNotNull($resolved);
        $this->assertSame($docs->id, $resolved->id);
        $this->assertSame('standard', $resolved->content_width);
    }

    public function test_search_inherits_docs_layout_via_peer_channel(): void
    {
        ChromeLayout::query()->create([
            'name' => 'Site default',
            'slug' => 'site-default',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            'channel_ids' => [],
            'content_width' => 'full',
        ]);

        $docs = ChromeLayout::query()->create([
            'name' => 'Docs chrome',
            'slug' => 'docs-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => false,
            'channel_ids' => ['docs'],
            'content_width' => 'standard',
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel('search');

        $this->assertNotNull($resolved);
        $this->assertSame($docs->id, $resolved->id);
        $this->assertSame('standard', $resolved->content_width);
    }

    public function test_explicit_tutorials_layout_beats_docs_peer(): void
    {
        $docs = ChromeLayout::query()->create([
            'name' => 'Docs chrome',
            'slug' => 'docs-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => false,
            'channel_ids' => ['docs'],
            'content_width' => 'standard',
        ]);

        $tutorials = ChromeLayout::query()->create([
            'name' => 'Tutorials chrome',
            'slug' => 'tutorials-chrome',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => false,
            'channel_ids' => ['tutorials'],
            'content_width' => 'full',
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel('tutorials');

        $this->assertNotNull($resolved);
        $this->assertSame($tutorials->id, $resolved->id);
        $this->assertNotSame($docs->id, $resolved->id);
    }

    public function test_draft_shell_layout_falls_back_to_default(): void
    {
        $default = ChromeLayout::query()->create([
            'name' => 'Site default',
            'slug' => 'site-default',
            'html' => '<div data-voodbuilder-block="site_nav_simple"></div><div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => true,
            'channel_ids' => [],
        ]);

        ChromeLayout::query()->create([
            'name' => 'Events draft',
            'slug' => 'events-draft',
            'html' => '<div data-voodbuilder-chrome-drop-zone="nav" class="voodbuilder-chrome-drop-zone"></div><div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'is_default' => false,
            'channel_ids' => ['events'],
        ]);

        ChromeLayoutResolver::forgetCache();

        $resolved = ChromeLayoutResolver::resolveForChannel('events');

        $this->assertNotNull($resolved);
        $this->assertSame($default->id, $resolved->id);
    }
}
