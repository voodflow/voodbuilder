@php
    $initialPayload = $builder_payload ?? ['html' => '', 'css' => '', 'project' => null];
@endphp

<div
    wire:ignore
    class="vpress-grapesjs-field"
    x-data="vpressGrapesJsBuilder({
        initial: @js($initialPayload),
        blocks: @js($blocks),
        uploadUrl: @js($uploadUrl),
        canvasStyles: @js($canvasStyles),
    })"
>
    <div
        x-ref="editor"
        class="vpress-grapesjs-canvas min-h-[640px] rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
    ></div>
</div>
