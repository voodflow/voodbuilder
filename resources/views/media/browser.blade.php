{{--
  Frontend media browser for the page builder / custom pickers.
  Expects $assets as list of {src, type, name, uuid?, id?} from EditorMediaLibrary::listAssets().
--}}
@props([
    'assets' => [],
    'type' => null,
])

@php
    $items = collect($assets)
        ->when(filled($type), fn ($c) => $c->where('type', $type))
        ->values();
@endphp

<div {{ $attributes->class(['voodbuilder-media-browser']) }} data-voodbuilder-media-browser>
    @if ($items->isEmpty())
        <p class="voodbuilder-media-browser__empty">
            {{ __('voodbuilder::admin.media_library.empty') }}
        </p>
    @else
        <ul class="voodbuilder-media-browser__grid" role="list">
            @foreach ($items as $asset)
                <li class="voodbuilder-media-browser__item" data-media-type="{{ $asset['type'] ?? 'image' }}">
                    <button
                        type="button"
                        class="voodbuilder-media-browser__pick"
                        data-media-src="{{ $asset['src'] }}"
                        data-media-uuid="{{ $asset['uuid'] ?? '' }}"
                        data-media-id="{{ $asset['id'] ?? '' }}"
                        data-media-name="{{ $asset['name'] ?? '' }}"
                    >
                        @if (($asset['type'] ?? 'image') === 'video')
                            <video
                                class="voodbuilder-media-browser__thumb"
                                src="{{ $asset['src'] }}"
                                muted
                                playsinline
                                preload="metadata"
                            ></video>
                        @else
                            <img
                                class="voodbuilder-media-browser__thumb"
                                src="{{ $asset['src'] }}"
                                alt="{{ $asset['name'] ?? '' }}"
                                loading="lazy"
                            >
                        @endif
                        <span class="voodbuilder-media-browser__name">{{ $asset['name'] ?? '' }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
