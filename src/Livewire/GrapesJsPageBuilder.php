<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsCanvas;

class GrapesJsPageBuilder extends Component
{
    /** @var array{html?: string, css?: string, project?: array<string, mixed>}|null */
    public ?array $builder_payload = null;

    public function render(): View
    {
        return view('vpress::livewire.grapesjs-page-builder', [
            'blocks' => app(GrapesJsBlockRegistry::class)->toEditorBlocks(),
            'uploadUrl' => route('vpress.grapesjs.upload'),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'builder_payload' => $this->builder_payload ?? ['html' => '', 'css' => '', 'project' => null],
        ]);
    }
}
