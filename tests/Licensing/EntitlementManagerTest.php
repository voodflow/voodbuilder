<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Voodflow\Voodbuilder\Licensing\CapabilitySet;
use Voodflow\Voodbuilder\Licensing\ConfigEntitlementProvider;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\EntitlementManager;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class EntitlementManagerTest extends TestCase
{
    public function test_agency_edition_is_default_in_testbench(): void
    {
        $this->assertSame('agency', Voodbuilder::entitlements()->edition());
        $this->assertTrue(Voodbuilder::can('components.library'));
        $this->assertTrue(Voodbuilder::can('dynamic-data.collections'));
        $this->assertTrue(Voodbuilder::can('templates.local'));
    }

    public function test_community_matrix_excludes_agency_and_pro_surfaces(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $this->assertTrue(Voodbuilder::can('editor.core'));
        $this->assertTrue(Voodbuilder::can('templates.local'));
        $this->assertTrue(Voodbuilder::can('popups.builder'));

        $this->assertTrue(Voodbuilder::cannot('dynamic-data.single'));
        $this->assertTrue(Voodbuilder::cannot('components.library'));
        $this->assertTrue(Voodbuilder::cannot('templates.import'));
        $this->assertTrue(Voodbuilder::cannot('dynamic-data.collections'));
        $this->assertTrue(Voodbuilder::cannot('themes.export'));
    }

    public function test_professional_matrix_adds_collections_and_template_import(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_PROFESSIONAL),
        );

        $this->assertTrue(Voodbuilder::can('dynamic-data.collections'));
        $this->assertTrue(Voodbuilder::can('templates.import'));
        $this->assertTrue(Voodbuilder::cannot('components.library'));
        $this->assertTrue(Voodbuilder::cannot('templates.export'));
    }

    public function test_testing_provider_can_simulate_custom_capability_sets(): void
    {
        $provider = new TestingEntitlementProvider(['editor.core', 'popups.builder']);
        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertTrue(Voodbuilder::can('editor.core'));
        $this->assertTrue(Voodbuilder::entitlements()->canAny(['missing', 'popups.builder']));
        $this->assertFalse(Voodbuilder::entitlements()->canAll(['editor.core', 'components.library']));

        $provider->grant(['components.library']);
        $this->assertTrue(Voodbuilder::can('components.library'));
    }

    public function test_capability_set_merge_and_sort(): void
    {
        $set = CapabilitySet::from(['b.cap', 'a.cap'])->merge(CapabilitySet::from(['c.cap', 'a.cap']));

        $this->assertSame(['a.cap', 'b.cap', 'c.cap'], $set->all());
        $this->assertSame(3, $set->count());
    }

    public function test_config_provider_follows_edition_setting(): void
    {
        config()->set('voodbuilder.license.edition', 'community');
        config()->set('voodbuilder.license.cache', false);

        $manager = new EntitlementManager(new ConfigEntitlementProvider);

        $this->assertSame('community', $manager->edition());
        $this->assertTrue($manager->can('menus.admin'));
        $this->assertFalse($manager->can('components.create'));
    }
}
