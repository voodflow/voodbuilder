<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingField;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Server-side data source for Editor field bindings (data-voodbuilder-bind).
 *
 * Register with {@see Voodbuilder::editorBindingSource()} in your
 * package ServiceProvider. See docs/BINDINGS.md for the full plugin guide.
 *
 * Optional: implement legacyFieldIds() to keep old field keys valid after renames.
 */
interface EditorBindingSource
{
    public function id(): string;

    public function label(): string;

    /** Package slug used to group sources in the editor UI (e.g. vtuts). */
    public function package(): string;

    public function packageLabel(): string;

    /** @return list<BindingField> */
    public function fields(): array;

    public function resolve(string $fieldId, BindingContext $context): ?string;
}
