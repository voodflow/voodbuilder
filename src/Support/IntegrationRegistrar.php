<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Loads host-app integrations from config without modifying vendor packages.
 *
 * Publish or create `config/voodbuilder-integrations.php` in the host app.
 */
final class IntegrationRegistrar
{
    public static function boot(): void
    {
        $path = config_path('voodbuilder-integrations.php');

        if (! is_file($path)) {
            return;
        }

        /** @var array<string, mixed> $integrations */
        $integrations = require $path;

        foreach ($integrations['content_channels'] ?? [] as $id => $definition) {
            if (! is_string($id) || ! is_array($definition)) {
                continue;
            }

            Voodbuilder::contentChannel($id, $definition);
        }
    }
}
