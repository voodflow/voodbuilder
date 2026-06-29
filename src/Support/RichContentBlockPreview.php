<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

class RichContentBlockPreview
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function render(string $view, array $data = []): string
    {
        return self::wrap(view($view, $data)->render());
    }

    public static function wrap(string $html): string
    {
        return view('voodbuilder::blocks.preview-shell', [
            'content' => $html,
        ])->render();
    }
}
