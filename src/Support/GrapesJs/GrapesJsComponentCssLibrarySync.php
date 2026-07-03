<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\BuilderComponent;

final class GrapesJsComponentCssLibrarySync
{
    public function syncFromPageHtml(string $pageHtml): void
    {
        foreach (GrapesJsComponentPageHtml::componentIds($pageHtml) as $componentId) {
            $this->syncComponentFromPageHtml($pageHtml, $componentId);
        }
    }

    protected function syncComponentFromPageHtml(string $pageHtml, string $componentId): void
    {
        $instanceHtml = GrapesJsComponentPageHtml::instanceInnerHtml($pageHtml, $componentId);

        if ($instanceHtml === null) {
            return;
        }

        $component = BuilderComponent::query()->find($componentId);

        if ($component === null) {
            return;
        }

        $storedCss = filled($component->css) ? trim((string) $component->css) : '';

        if ($storedCss !== '' && ! GrapesJsPastedComponentNormalizer::storedCssRequiresRecompile($instanceHtml, $storedCss)) {
            return;
        }

        $compiledCss = GrapesJsPastedComponentNormalizer::compileCssForComponentInstanceHtml($instanceHtml);

        if ($compiledCss === null || $compiledCss === '') {
            return;
        }

        $manualCss = GrapesJsPastedComponentNormalizer::manualCssFromStoredComponentCss($storedCss);
        $css = GrapesJsPastedComponentNormalizer::mergeCss(
            $manualCss !== '' ? $manualCss : null,
            $compiledCss,
        );

        if ($css === '' || $css === $storedCss) {
            return;
        }

        $component->update(['css' => $css]);
    }
}
