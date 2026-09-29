<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Optional Voodflow automation bridge for VoodBuilder.
 *
 * VoodBuilder runs without voodflow/voodflow. When Voodflow is installed,
 * companion workflow nodes under src/Nodes are registered.
 */
final class VoodflowIntegration
{
    private const VOODFLOW_CLASS = 'Voodflow\\Voodflow\\Voodflow';

    public static function available(): bool
    {
        return class_exists(self::VOODFLOW_CLASS);
    }

    public static function enabled(): bool
    {
        if (! self::available()) {
            return false;
        }

        return (bool) config('voodbuilder.features.voodflow', true);
    }

    public static function nodesPath(): string
    {
        return dirname(__DIR__).'/Nodes';
    }
}
