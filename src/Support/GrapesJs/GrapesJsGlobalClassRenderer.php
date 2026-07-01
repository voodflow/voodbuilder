<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\BuilderGlobalClass;

final class GrapesJsGlobalClassRenderer
{
    public function css(): string
    {
        if (! Schema::hasTable('voodbuilder_global_classes')) {
            return '';
        }

        $chunks = BuilderGlobalClass::query()
            ->orderBy('name')
            ->pluck('css')
            ->filter(fn (mixed $css): bool => filled($css))
            ->map(fn (mixed $css): string => GrapesJsCssSanitizer::sanitize((string) $css))
            ->all();

        return implode("\n", $chunks);
    }
}
