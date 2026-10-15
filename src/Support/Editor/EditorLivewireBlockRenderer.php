<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Livewire\Livewire;
use Voodflow\Voodbuilder\Contracts\EditorLivewireBlock;

/**
 * Mounts the Livewire component behind an {@see EditorLivewireBlock} for the
 * published page, with the static preview as the fallback surface.
 *
 * Deliberately kept away from `EditorRichContentBlockAdapter::prepareBlockHtml()`:
 * the class / placeholder / button normalizers that make authored markup
 * publishable rewrite regenerated markup, and they would mangle component HTML
 * (e.g. morphing interactive `<button>` controls into anchors) and break
 * hydration.
 */
final class EditorLivewireBlockRenderer
{
    /**
     * @param  class-string<EditorLivewireBlock>  $blockClass
     * @param  array<string, mixed>  $config
     * @param  string|null  $instanceKey  stable per-node key. When null Livewire
     *                                    generates a unique component id per mount,
     *                                    which is what keeps repeated blocks from
     *                                    colliding on the same page.
     */
    public static function mount(string $blockClass, array $config, ?string $instanceKey = null): string
    {
        if (! self::livewireAvailable()) {
            return self::preview($blockClass, $config);
        }

        try {
            return (string) Livewire::mount(
                $blockClass::component(),
                $blockClass::propsFromConfig($config),
                $instanceKey,
            );
        } catch (\Throwable $exception) {
            // A missing, renamed or misconfigured component must degrade to the
            // static preview rather than take the whole page down.
            report($exception);

            return self::preview($blockClass, $config);
        }
    }

    /**
     * Static canvas preview — same surface the editor shows and saves.
     *
     * @param  class-string<EditorLivewireBlock>  $blockClass
     * @param  array<string, mixed>  $config
     */
    public static function preview(string $blockClass, array $config): string
    {
        return EditorRichContentBlockAdapter::prepareBlockHtml(
            $blockClass::toPreviewHtml($config, []),
        );
    }

    public static function livewireAvailable(): bool
    {
        return class_exists(Livewire::class);
    }
}
