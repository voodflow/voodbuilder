<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Illuminate\Routing\Router;
use Voodflow\Voodbuilder\Modules\ModuleContext;

interface RegistersRoutes
{
    public function registerRoutes(Router $router, ModuleContext $context): void;
}
