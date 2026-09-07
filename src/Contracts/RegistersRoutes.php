<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Illuminate\Routing\Router;
use Voodflow\Voodbuilder\Modules\ModuleContext;

/**
 * Module capability: register HTTP routes during module boot.
 */
interface RegistersRoutes
{
    public function registerRoutes(Router $router, ModuleContext $context): void;
}
