<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ModelIntegrationBindingRegistrar;
use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * `.item` binding sources sit on both sides of the commercial boundary.
 *
 * They stay registered whatever the licence says, because a published page needs them to
 * resolve the fields inside a repeat it already contains. But offering them in the editor
 * without collections would be a dead end — an `.item` binding only resolves inside a
 * repeat, and an author who cannot create one has nowhere to use it.
 */
final class EditorBindingsCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        if (! DynamicDataCollectionsBridge::renderingEnabled()) {
            $this->markTestSkipped('voodbuilder-dynamic-data companion package is not available.');
        }

        $author = new User;
        $author->forceFill(['id' => 1, 'name' => 'Author', 'email' => 'author@example.test'])->save();

        $this->actingAs($author);

        $integration = ModelIntegration::query()->create([
            'name' => 'Articles',
            'model_alias' => 'articles',
            'model_class' => User::class,
            'fields' => [],
        ]);

        app(ModelIntegrationBindingRegistrar::class)->register($integration);
    }

    private function sourceIds(): array
    {
        return array_map(
            static fn (array $source): string => $source['id'],
            app(BindingRegistry::class)->catalog(),
        );
    }

    public function test_item_sources_stay_registered_on_community_so_published_pages_resolve(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        // Registration happened in setUp, before the edition was narrowed — which is the
        // realistic order: the registrar runs at boot. What matters is that the registrar
        // never asked about the entitlement in the first place.
        $this->assertContains('articles.item', $this->sourceIds());
    }

    public function test_the_picker_hides_item_sources_without_collections(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $payload = $this->getJson(route('voodbuilder.editor.bindings'))
            ->assertOk()
            ->json();

        $offered = array_column($payload['sources'], 'id');

        $this->assertNotContains('articles.item', $offered);
        $this->assertContains('articles.latest', $offered);
        $this->assertSame([], $payload['repeatSources']);

        // Grouping must agree with the flat list, or the panel shows a package header with
        // nothing under it.
        foreach ($payload['groups'] as $group) {
            $this->assertNotSame([], $group['sources']);

            foreach ($group['sources'] as $source) {
                $this->assertStringEndsNotWith('.item', $source['id']);
            }
        }
    }

    public function test_the_picker_offers_item_sources_with_collections(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_PROFESSIONAL),
        );

        $payload = $this->getJson(route('voodbuilder.editor.bindings'))
            ->assertOk()
            ->json();

        $this->assertContains('articles.item', array_column($payload['sources'], 'id'));
    }
}
