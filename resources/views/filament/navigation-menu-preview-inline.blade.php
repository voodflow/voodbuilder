<div
    class="vb-filament-menu-preview relative overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
    wire:key="menu-preview-{{ $previewKey }}"
>
    <div class="pointer-events-none absolute top-2 right-2 z-10">
        <span class="rounded-md border border-dashed border-gray-300 bg-white px-2 py-1 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400">
            {{ __('voodbuilder::admin.menu_preview.badge', ['menu' => $menuName]) }}
        </span>
    </div>

    <div class="vb-filament-menu-preview__canvas overflow-x-auto">
        <div class="min-w-[48rem] bg-vp-bg text-vp-text-1">
            {!! $previewHtml !!}
        </div>
    </div>
</div>
