<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class ConfigureVtutsForVpress
{
    public static function apply(bool $force = false): bool
    {
        $path = config_path('vtuts.php');

        if (! is_file($path)) {
            return false;
        }

        $contents = File::get($path);
        $original = $contents;

        $contents = str_replace(
            "'layout' => 'vtuts::layouts.page'",
            "'layout' => 'vpress::layouts.page'",
            $contents,
        );

        $contents = str_replace(
            "'doc_layout' => 'vtuts::layouts.doc'",
            "'doc_layout' => 'vpress::layouts.doc'",
            $contents,
        );

        if ($force || str_contains($contents, "'fallback_url' => null")) {
            $contents = str_replace(
                "'fallback_url' => null",
                <<<'PHP'
'fallback_url' => fn (string $locale): string => \Voodflow\Vpress\Support\VpressUrls::home()
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
