<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Voodflow\Vmedia\Vmedia;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * The editor cannot open without somewhere to put an image.
 *
 * Media lives in voodflow/vmedia, a hard requirement. Registering VmediaPlugin on a Filament
 * panel is what normally brings its HTTP routes up, but that is a panel concern and the
 * editor is not: a host may keep the media screens out of the sidebar and still expect the
 * canvas to accept an upload. So this package activates the media runtime itself.
 */
final class MediaRuntimeTest extends TestCase
{
    public function test_the_media_endpoints_exist_without_any_filament_panel(): void
    {
        // No panel, no plugin — nothing in this suite registers VmediaPlugin.
        $this->assertTrue(Route::has('vmedia.media.upload'));
        $this->assertTrue(Route::has('vmedia.media.index'));
        $this->assertTrue(Route::has('vmedia.media.galleries'));
    }

    public function test_the_editor_config_carries_a_usable_upload_url(): void
    {
        $config = EditorGate::config($this->pageForEditor());

        $this->assertNotSame(
            '',
            (string) ($config['uploadUrl'] ?? ''),
            'An empty upload URL is what previously 500d ?edit=1 and lost saves.',
        );
    }

    public function test_the_gallery_browser_is_offered_rather_than_the_bare_asset_manager(): void
    {
        $config = EditorGate::config($this->pageForEditor());

        $this->assertNotNull(
            $config['mediaGalleriesUrl'] ?? null,
            'Without a galleries URL the editor falls back to the paginated-blind asset manager.',
        );
    }

    /**
     * Guards the regression that the route-based check in ensureMediaRuntime() exists for.
     *
     * Vmedia's active flag is static, so it survives an application refresh while the router
     * does not. Trusting the flag meant the second test in a run booted an editor with no
     * upload endpoint; the routes have to come back for each fresh application.
     */
    public function test_the_runtime_recovers_when_the_active_flag_outlives_the_router(): void
    {
        Vmedia::activate();
        $this->assertTrue(Vmedia::isActive(), 'Sanity: the flag is set and would short-circuit.');

        $this->refreshApplication();

        $this->assertTrue(
            Route::has('vmedia.media.upload'),
            'A stale active flag must not leave the fresh application without media routes.',
        );
    }

    private function pageForEditor(): SitePage
    {
        EditorGate::authorizeUsing(static fn (): bool => true);

        return SitePage::query()->create([
            'title' => 'Landing page',
            'slug' => 'landing-page',
            'builder' => PageBuilder::Visual,
            'published' => true,
            'builder_payload' => ['html' => '<section>Hero</section>', 'css' => ''],
        ]);
    }
}
