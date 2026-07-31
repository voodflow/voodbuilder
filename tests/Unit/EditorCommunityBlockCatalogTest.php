<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Support\Editor\EditorCommunityBlockCatalog;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class EditorCommunityBlockCatalogTest extends TestCase
{
    public function test_community_keeps_foundation_and_allows_injected_ids(): void
    {
        $this->useEdition(EditionCapabilityMatrix::EDITION_COMMUNITY);

        $filtered = EditorCommunityBlockCatalog::filterEditorBlocks([
            ['id' => 'vb-hero-2', 'label' => 'Hero'],
            ['id' => 'vb-hero-1', 'label' => 'Hero extra'],
            ['id' => 'vb-gallery-1', 'label' => 'Gallery'],
            ['id' => 'chrome_content_slot', 'label' => 'Slot'],
            ['id' => 'voodbuilder-heading', 'label' => 'Heading'],
            ['id' => 'acme-custom', 'label' => 'Plugin'],
        ], chromeLayoutEditor: false);

        $ids = array_column($filtered, 'id');

        $this->assertContains('vb-hero-2', $ids);
        $this->assertContains('voodbuilder-heading', $ids);
        $this->assertContains('acme-custom', $ids);
        // After Elements extract, former companion IDs are not core-owned:
        // if registered (Elements plugin / third party) they stay visible.
        $this->assertContains('vb-hero-1', $ids);
        $this->assertContains('vb-gallery-1', $ids);
        $this->assertNotContains('chrome_content_slot', $ids);
        $this->assertFalse(EditorCommunityBlockCatalog::isCompanionBlockId('vb-hero-2'));
        $this->assertTrue(EditorCommunityBlockCatalog::isCompanionBlockId('vb-gallery-1'));
        $this->assertNotContains('vb-gallery-1', EditorCommunityBlockCatalog::coreOwnedIds());
    }

    public function test_community_chrome_editor_keeps_content_slot(): void
    {
        $this->useEdition(EditionCapabilityMatrix::EDITION_COMMUNITY);

        $filtered = EditorCommunityBlockCatalog::filterEditorBlocks([
            ['id' => 'chrome_content_slot', 'label' => 'Slot'],
            ['id' => 'site_nav_simple', 'label' => 'Nav'],
            ['id' => 'vb-gallery-1', 'label' => 'Gallery'],
        ], chromeLayoutEditor: true);

        $ids = array_column($filtered, 'id');

        $this->assertContains('chrome_content_slot', $ids);
        $this->assertContains('site_nav_simple', $ids);
        $this->assertContains('vb-gallery-1', $ids);
    }

    public function test_professional_shows_full_library_when_registered(): void
    {
        $this->useEdition(EditionCapabilityMatrix::EDITION_PROFESSIONAL);

        $this->assertTrue(Voodbuilder::can(EditorCommunityBlockCatalog::CAPABILITY_FULL_LIBRARY));
        $this->assertNull(EditorCommunityBlockCatalog::sidebarAllowlist(chromeLayoutEditor: false));

        $filtered = EditorCommunityBlockCatalog::filterEditorBlocks([
            ['id' => 'vb-gallery-1', 'label' => 'Gallery'],
            ['id' => 'chrome_content_slot', 'label' => 'Slot'],
        ], chromeLayoutEditor: false);

        $ids = array_column($filtered, 'id');

        $this->assertContains('vb-gallery-1', $ids);
        $this->assertNotContains('chrome_content_slot', $ids);
    }

    private function useEdition(string $edition): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition($edition),
        );
    }
}
