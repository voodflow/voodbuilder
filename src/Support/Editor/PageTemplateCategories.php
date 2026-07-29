<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class PageTemplateCategories
{
    /**
     * Template categories (distinct from component / block categories).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'Blogging',
            'Corporate',
            'Creative',
            'Ecommerce',
            'Landing pages',
            'Marketing',
            'Miscellaneous',
            'Personal',
        ];
    }
}
