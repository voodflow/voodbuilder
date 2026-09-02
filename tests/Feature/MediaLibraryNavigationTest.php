<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Vmedia\Vmedia;
use Voodflow\Voodbuilder\Filament\Resources\MediaLibraryResource;
use Voodflow\Voodbuilder\Support\MediaCompanion;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * Only one "Media library" belongs in the sidebar.
 *
 * This package ships a flat browser over its own MediaLibrary singleton; voodflow/vmedia
 * ships a full vault. Both label themselves "Media library", so with both registered the
 * admin offered two identical-looking entries pointing at different tables, and picking the
 * wrong one meant uploading into a library the editor was not reading from.
 */
final class MediaLibraryNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Vmedia::class)) {
            $this->markTestSkipped('voodflow/vmedia companion package is not available.');
        }
    }

    public function test_our_library_stands_down_when_the_companion_owns_the_sidebar(): void
    {
        Vmedia::reset();
        Vmedia::activate();

        $this->assertTrue(MediaCompanion::ownsAdminLibrary());
        $this->assertFalse(MediaLibraryResource::canAccess());
    }

    public function test_our_library_stays_when_the_companion_plugin_is_not_registered(): void
    {
        // Installing the package is not the same as registering its Filament plugin. A host
        // can pull vmedia in for the editor's asset browser and keep its admin resources
        // out of the panel; something still has to manage media in the sidebar.
        Vmedia::reset();

        $this->assertFalse(MediaCompanion::ownsAdminLibrary());
        $this->assertTrue(MediaLibraryResource::canAccess());
    }

    public function test_the_answer_survives_the_companion_activating_after_us(): void
    {
        // The regression this guards. VoodbuilderPlugin::register() used to decide this, but
        // the host lists VmediaPlugin after it, so the companion had not activated yet and
        // the check read "absent" every single time. The sequence below is that ordering:
        // ask first, activate second.
        Vmedia::reset();

        $this->assertTrue(MediaLibraryResource::canAccess(), 'Sanity: nothing owns the sidebar yet.');

        Vmedia::activate();

        $this->assertFalse(
            MediaLibraryResource::canAccess(),
            'Asked again after the companion registered, the answer has to change.',
        );
    }

    public function test_config_still_switches_our_library_off_on_its_own(): void
    {
        Vmedia::reset();

        config()->set('voodbuilder.media_library.enabled', false);

        $this->assertFalse(MediaLibraryResource::canAccess());
    }

    public function test_the_companion_check_survives_a_bare_install(): void
    {
        // MediaCompanion resolves vmedia by string precisely so this cannot fatal when the
        // optional package is absent. Nothing to skip here: the guards are the assertion.
        $this->assertIsBool(MediaCompanion::isActive());
        $this->assertIsBool(MediaCompanion::ownsAdminLibrary());
    }
}
