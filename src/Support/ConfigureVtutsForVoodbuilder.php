<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Align published vtuts config with Voodbuilder hosts (locale fallback only).
 * Reading layouts stay owned by vtuts (`vtuts::layouts.voodbuilder`).
 */
final class ConfigureVtutsForVoodbuilder
{
    public static function apply(bool $force = false): bool
    {
        $path = config_path('vtuts.php');

        if (! is_file($path)) {
            return false;
        }

        $contents = File::get($path);
        $original = $contents;

        $layoutReplacements = [
            "'layout' => 'voodbuilder::layouts.page'" => "'layout' => 'vtuts::layouts.voodbuilder-page'",
            "'doc_layout' => 'voodbuilder::layouts.doc'" => "'doc_layout' => 'vtuts::layouts.voodbuilder'",
        ];

        foreach ($layoutReplacements as $search => $replace) {
            $contents = str_replace($search, $replace, $contents);
        }

        if ($force || str_contains($contents, "'fallback_url' => null")) {
            $contents = str_replace(
                "'fallback_url' => null",
                <<<'PHP'
'fallback_url' => fn (string $locale): string => \Voodflow\Vtuts\Support\LocaleSwitcher::currentPageUrlWithLocale($locale)
PHP,
                $contents,
            );
        }

        if ($contents === $original) {
            return false;
        }

        File::put($path, $contents);

        return true;
    }
}
