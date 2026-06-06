<div
    class="relative"
    x-data="{ open: @entangle('open') }"
    @click.outside="open = false"
    wire:poll.30s
>
    <button
        type="button"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-md text-vp-text-2 transition-colors hover:text-vp-text-1"
        aria-label="{{ __('vpress::notifications.bell_label') }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        aria-haspopup="true"
        wire:click="toggle"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if ($this->unreadCount > 0)
            <span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-vp-brand-1 px-1 text-[10px] font-semibold text-white" aria-hidden="true">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        role="menu"
        aria-label="{{ __('vpress::notifications.panel_label') }}"
        class="absolute top-[calc(100%+0.5rem)] right-0 z-50 w-80 overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv shadow-lg"
    >
        <div class="flex items-center justify-between gap-2 border-b border-vp-divider px-4 py-3">
            <span class="text-sm font-semibold text-vp-text-1">{{ __('vpress::notifications.panel_title') }}</span>
            <div class="flex items-center gap-2">
                @if ($this->unreadCount > 0)
                    <button type="button" class="text-xs text-vp-brand-1 hover:underline" wire:click="markAllAsRead">
                        {{ __('vpress::notifications.mark_all_read') }}
                    </button>
                @endif
                @if ($this->items->isNotEmpty())
                    <button type="button" class="text-xs text-vp-text-2 hover:text-vp-text-1 hover:underline" wire:click="clearAll">
                        {{ __('vpress::notifications.clear_all') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse ($this->items as $item)
                <div @class([
                    'group relative border-b border-vp-divider last:border-b-0',
                    'bg-vp-gray-soft/40' => ! $item['read'],
                ])>
                    @if (filled($item['url']))
                        <a
                            href="{{ $item['url'] }}"
                            class="block px-4 py-3 pr-10 text-left transition-colors hover:bg-vp-gray-soft"
                            role="menuitem"
                            wire:navigate="false"
                            wire:click="markAsRead('{{ $item['id'] }}')"
                        >
                            <span class="block text-sm font-medium text-vp-text-1">{{ $item['title'] }}</span>
                            <span class="mt-0.5 block text-xs text-vp-text-2">{{ $item['body'] }}</span>
                            <span class="mt-1 block text-[11px] text-vp-text-3">{{ $item['created_at'] }}</span>
                        </a>
                    @else
                        <button
                            type="button"
                            class="block w-full px-4 py-3 pr-10 text-left transition-colors hover:bg-vp-gray-soft"
                            role="menuitem"
                            wire:click="markAsRead('{{ $item['id'] }}')"
                        >
                            <span class="block text-sm font-medium text-vp-text-1">{{ $item['title'] }}</span>
                            <span class="mt-0.5 block text-xs text-vp-text-2">{{ $item['body'] }}</span>
                            <span class="mt-1 block text-[11px] text-vp-text-3">{{ $item['created_at'] }}</span>
                        </button>
                    @endif
                    <button
                        type="button"
                        class="absolute top-2 right-2 inline-flex h-7 w-7 items-center justify-center rounded-md text-vp-text-3 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-vp-gray-soft hover:text-vp-text-1"
                        aria-label="{{ __('vpress::notifications.delete') }}"
                        wire:click.stop="deleteNotification('{{ $item['id'] }}')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-vp-text-2">{{ __('vpress::notifications.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
