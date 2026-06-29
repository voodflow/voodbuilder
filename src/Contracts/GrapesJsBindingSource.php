<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Contracts;

use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingField;

/**
 * Server-side data source for GrapesJS field bindings (data-vpress-bind).
 *
 * Register with {@see \Voodflow\Vpress\Vpress::grapesJsBindingSource()} in your
 * package ServiceProvider. See docs/BINDINGS.md for the full plugin guide.
 *
 * Optional: implement legacyFieldIds() to keep old field keys valid after renames.
 */
interface GrapesJsBindingSource
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
