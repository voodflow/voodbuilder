<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Voodflow\Voodbuilder\Licensing\CapabilitySet;
use Voodflow\Voodbuilder\Licensing\CommunityEntitlementProvider;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\EditorEditionSummary;
use Voodflow\Voodbuilder\Licensing\EntitlementManager;
use Voodflow\Voodbuilder\Licensing\EntitlementProviderFactory;
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
        $this->assertTrue(Voodbuilder::cannot('dynamic-api.sources'));
    }

    public function test_developer_alias_matches_professional_matrix(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_DEVELOPER),
        );

        $this->assertSame(
            EditionCapabilityMatrix::professional(),
            EditionCapabilityMatrix::forEdition(EditionCapabilityMatrix::EDITION_DEVELOPER),
        );
        $this->assertSame('professional', Voodbuilder::entitlements()->edition());
        $this->assertSame(
            'Developer edition',
            EditionCapabilityMatrix::marketingLabel(EditionCapabilityMatrix::EDITION_DEVELOPER),
        );
        $this->assertSame(
            'developer',
            EditionCapabilityMatrix::marketingSlug(EditionCapabilityMatrix::EDITION_DEVELOPER),
        );
        $this->assertSame(
            'Developer edition',
            EditionCapabilityMatrix::marketingLabel('professional'),
        );
        $this->assertSame(
            'Agency edition',
            EditionCapabilityMatrix::marketingLabel(EditionCapabilityMatrix::EDITION_AGENCY),
        );
        $this->assertSame(
            'Community edition',
            EditionCapabilityMatrix::marketingLabel(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );
        $summary = EditorEditionSummary::make();
        $this->assertSame('developer', $summary['slug']);
        $this->assertSame('Developer edition', $summary['label']);
        $this->assertNotSame('', $summary['package_version']);
        $this->assertArrayHasKey('package_status', $summary);
        $this->assertArrayHasKey('package_status_label', $summary);
        $this->assertSame('https://docs.voodflow.com', $summary['docs_url']);
        $this->assertTrue(Voodbuilder::can('dynamic-data.collections'));
        $this->assertTrue(Voodbuilder::cannot('dynamic-api.sources'));
    }

    public function test_agency_matrix_includes_dynamic_api(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_AGENCY),
        );

        $this->assertTrue(Voodbuilder::can('dynamic-api.sources'));
        $this->assertTrue(Voodbuilder::can('components.library'));
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

    public function test_community_provider_is_fixed(): void
    {
        $manager = new EntitlementManager(new CommunityEntitlementProvider);

        $this->assertSame('community', $manager->edition());
        $this->assertTrue($manager->can('menus.admin'));
        $this->assertFalse($manager->can('components.create'));
    }

    public function test_factory_without_key_stays_community(): void
    {
        config()->set('voodbuilder.license.driver', 'anystack');
        config()->set('voodbuilder.license.key', '');
        config()->set('voodbuilder.license.cache', false);
        config()->set('voodbuilder.license.testing_edition', 'agency');

        $manager = new EntitlementManager(EntitlementProviderFactory::make());

        $this->assertSame('community', $manager->edition());
        $this->assertFalse($manager->can('dynamic-data.collections'));
        $this->assertFalse($manager->can('components.library'));
    }

    public function test_factory_ignores_testing_edition_outside_testing_driver(): void
    {
        config()->set('voodbuilder.license.driver', 'anystack');
        config()->set('voodbuilder.license.key', '');
        config()->set('voodbuilder.license.cache', false);
        config()->set('voodbuilder.license.testing_edition', 'agency');

        $provider = EntitlementProviderFactory::make();

        $this->assertInstanceOf(CommunityEntitlementProvider::class, $provider);
        $this->assertSame('community', $provider->licenceStatus()->edition);
    }
}
