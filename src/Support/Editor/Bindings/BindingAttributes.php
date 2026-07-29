<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

/**
 * HTML attribute names for Editor field bindings.
 */
final class BindingAttributes
{
    public const BIND = 'data-voodbuilder-bind';

    /**
     * Independent URL/href binding (CTA / link / rich-text anchors).
     * Content/label stays on {@see self::BIND} when type is text.
     */
    public const BIND_HREF = 'data-voodbuilder-bind-href';

    /**
     * Remove the element on the public site when its binding(s) resolve empty.
     */
    public const HIDE_WHEN_EMPTY = 'data-voodbuilder-hide-when-empty';
}
