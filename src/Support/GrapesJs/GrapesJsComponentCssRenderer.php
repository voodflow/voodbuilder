<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\BuilderComponent;

final class GrapesJsComponentCssRenderer
{
    public function cssForHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-component')) {
            return '';
        }

        if (! preg_match_all('/\bdata-voodbuilder-component=(["\'])([^"\']+)\1/i', $html, $matches)) {
            return '';
        }

        $ids = array_values(array_unique(array_filter($matches[2])));

        if ($ids === []) {
            return '';
        }

        return BuilderComponent::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->map(function (BuilderComponent $component): string {
                $html = TailblocksThemeTokenMigrator::migrateHtml((string) $component->html);

                return GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($html, $component->css);
            })
            ->filter(fn (string $css): bool => $css !== '')
            ->implode("\n\n");
    }
}
