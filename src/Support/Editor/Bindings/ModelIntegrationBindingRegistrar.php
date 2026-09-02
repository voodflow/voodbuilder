<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;

/**
 * Model Integration Binding Registrar.
 */
final class ModelIntegrationBindingRegistrar
{
    public function __construct(
        private readonly BindingRegistry $bindings,
        private readonly ModelIntegrationRegistry $integrations,
    ) {}

    public function register(ModelIntegration $integration): void
    {
        $this->integrations->register($integration);

        $this->bindings->register(new ModelIntegrationLatestBindingSource($integration));

        // `.item` bindings are how a field inside an already-published repeat resolves, so
        // registration follows the render path, not the entitlement: a page that stopped
        // resolving its own bindings would render the placeholder text to visitors. The
        // editor decides separately whether to *offer* them — see EditorBindingsController.
        if (
            DynamicDataCollectionsBridge::renderingEnabled()
            && class_exists(ModelIntegrationItemBindingSource::class)
        ) {
            $this->bindings->register(new ModelIntegrationItemBindingSource($integration));
        }

        if (ModelIntegrationAuthBindingSource::supports($integration)) {
            $this->bindings->register(new ModelIntegrationAuthBindingSource($integration));
        }
    }

    public function unregister(ModelIntegration $integration): void
    {
        $alias = $integration->getAlias();

        $this->bindings->forget($alias.'.latest');
        $this->bindings->forget($alias.'.item');
        $this->bindings->forget($alias.'.auth');
        $this->integrations->forget($integration);
    }

    public function refreshFromDatabase(): void
    {
        foreach ($this->integrations->all() as $integration) {
            $this->unregister($integration);
        }

        if (! class_exists(ModelIntegration::class)) {
            return;
        }

        try {
            ModelIntegration::query()->each(function (ModelIntegration $integration): void {
                $integration->registerOnBoot();
                $this->register($integration);
            });
        } catch (\Throwable) {
            // Table may not exist before migrate.
        }
    }
}
