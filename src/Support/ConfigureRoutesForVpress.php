<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class ConfigureRoutesForVpress
{
    private const ROUTES_STUB = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

/*
| Public routes are registered by voodflow/vpress (home, pages, search, auth).
| Add application-specific routes below.
*/

PHP;

    /**
     * @var list<string>
     */
    private const WELCOME_ROUTE_PATTERNS = [
        "/Route::get\\('\\/', function \\(\\) \\{\\s*return view\\('welcome'\\);\\s*\\}\\);?\\s*\\n?/",
        "/Route::view\\('\\/', 'welcome'\\);?\\s*\\n?/",
    ];

    public static function apply(bool $force = false): bool
    {
        $path = base_path('routes/web.php');

        if (! is_file($path)) {
            return false;
        }

        $contents = File::get($path);
        $original = $contents;

        foreach (self::WELCOME_ROUTE_PATTERNS as $pattern) {
            $contents = preg_replace($pattern, '', $contents) ?? $contents;
        }

        if (self::isEmptyRoutesFile($contents)) {
            $contents = self::ROUTES_STUB;
        }

        if (! $force && $contents === $original) {
            return false;
        }

        if ($contents === $original) {
            return false;
        }

        File::put($path, $contents);

        return true;
    }

    private static function isEmptyRoutesFile(string $contents): bool
    {
        $normalized = preg_replace('/^<\?php\s*/', '', $contents) ?? $contents;
        $normalized = preg_replace('/use Illuminate\\\\Support\\\\Facades\\\\Route;\s*/', '', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        return $normalized === '' || $normalized === '<?php';
    }
}
