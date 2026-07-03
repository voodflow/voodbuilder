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

        $ids = GrapesJsComponentPageHtml::componentIds($html);

        if ($ids === []) {
            return '';
        }

        return BuilderComponent::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->map(function (BuilderComponent $component): string {
                $catalogHtml = VoodbuilderThemeTokenMigrator::migrateHtml((string) $component->html);

                return GrapesJsComponentInstanceCssScoper::scopeCssToComponentInstance(
                    GrapesJsPastedComponentNormalizer::publishedCssForStoredHtml($catalogHtml, $component->css),
                    (string) $component->id,
                );
            })
            ->filter(fn (string $css): bool => $css !== '')
            ->implode("\n\n");
    }
}
