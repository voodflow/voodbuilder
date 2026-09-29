<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Illuminate\Support\ServiceProvider;
use Voodflow\Voodbuilder\Support\VoodflowIntegration;
use Voodflow\Voodflow\Voodflow;

/**
 * Registers VoodBuilder workflow nodes when voodflow/voodflow is installed.
 */
class VoodbuilderVoodflowServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! VoodflowIntegration::enabled()) {
            return;
        }

        $nodesPath = VoodflowIntegration::nodesPath();

        Voodflow::nodePath($nodesPath);

        config([
            'voodflow.node_paths' => array_values(array_unique([
                ...((array) config('voodflow.node_paths', [])),
                $nodesPath,
            ])),
        ]);
    }
}
