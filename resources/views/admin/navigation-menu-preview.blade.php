@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteHeaderGrapesJsBlock;
    use Voodflow\Voodbuilder\Support\Navigation;
@endphp

@extends('voodbuilder::layouts.admin-preview', [
    'badge' => __('voodbuilder::admin.menu_preview.badge', ['menu' => $menu->name]),
])

@section('preview')
    @if ($isHeaderPlacement)
        <div data-voodbuilder-menu-preview="{{ $placement }}">
            @php
                $headerConfig = array_merge(SiteHeaderGrapesJsBlock::defaultConfig(), [
                    'show_notifications' => false,
                ]);
            @endphp
            {!! SiteHeaderGrapesJsBlock::toHtml($headerConfig, []) !!}
        </div>
    @elseif ($isFooterPlacement)
        <div class="flex min-h-screen flex-col justify-end" data-voodbuilder-menu-preview="{{ $placement }}">
            @if ($placement === 'footer')
                <x-voodbuilder::footer />
            @else
                <footer class="border-t border-vp-divider px-6 py-8 md:px-8">
                    <nav class="mx-auto flex max-w-6xl flex-col gap-2" aria-label="{{ $menu->name }}">
                        @forelse (Navigation::items($placement) as $item)
                            @include('voodbuilder::admin.partials.menu-preview-item', ['item' => $item])
                        @empty
                            <p class="text-sm text-vp-text-2">{{ __('voodbuilder::admin.menu_preview.empty') }}</p>
                        @endforelse
                    </nav>
                </footer>
            @endif
        </div>
    @else
        <div class="mx-auto max-w-3xl px-6 py-10" data-voodbuilder-menu-preview="{{ $placement }}">
            <p class="mb-4 text-sm text-vp-text-2">{{ __('voodbuilder::admin.menu_preview.standalone_help') }}</p>
            <nav class="flex flex-wrap gap-3 rounded-xl border border-vp-divider bg-vp-bg-alt p-6" aria-label="{{ $menu->name }}">
                @forelse (Navigation::items($placement) as $item)
                    @include('voodbuilder::admin.partials.menu-preview-item', ['item' => $item])
                @empty
                    <p class="text-sm text-vp-text-2">{{ __('voodbuilder::admin.menu_preview.empty') }}</p>
                @endforelse
            </nav>
        </div>
    @endif
@endsection
