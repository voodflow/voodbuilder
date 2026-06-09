<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

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
        return view('vpress::blocks.preview-shell', [
            'content' => $html,
        ])->render();
    }
}
