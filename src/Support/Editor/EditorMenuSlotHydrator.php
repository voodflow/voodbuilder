<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/*
 * @deprecated Use EditorSlotHydrator. EditorSlotHydrator is final, so the legacy name
 * is an alias (extending it was a fatal error on autoload).
 */
class_alias(EditorSlotHydrator::class, __NAMESPACE__ . '\\EditorMenuSlotHydrator');
