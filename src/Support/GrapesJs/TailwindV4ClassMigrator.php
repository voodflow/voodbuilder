<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

final class TailwindV4ClassMigrator
{
    /**
     * @var array<string, string>
     */
    private const REPLACEMENTS = [
        'flex-grow' => 'grow',
        'flex-shrink-0' => 'shrink-0',
        'flex-shrink' => 'shrink',
        'overflow-ellipsis' => 'text-ellipsis',
        'decoration-clone' => 'box-decoration-clone',
        'decoration-slice' => 'box-decoration-slice',
    ];

    public static function migrateHtml(string $html): string
    {
        foreach (self::REPLACEMENTS as $from => $to) {
            $html = preg_replace('/\b'.preg_quote($from, '/').'\b/', $to, $html) ?? $html;
        }

        return $html;
    }
}
