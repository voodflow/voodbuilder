<div
    class="VPNotificationBell"
    x-data="{ open: @entangle('open') }"
    @click.outside="open = false"
    wire:poll.30s
>
    <button
        type="button"
        class="VPNavBarAction VPNotificationBellTrigger"
        aria-label="{{ __('vpress::notifications.bell_label') }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        aria-haspopup="true"
        wire:click="toggle"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if ($this->unreadCount > 0)
            <span class="VPNotificationBellBadge" aria-hidden="true">{{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}</span>
        @endif
    </button>

    <div
        class="VPNotificationBellPanel"
        x-show="open"
        x-cloak
        x-transition
        role="menu"
        aria-label="{{ __('vpress::notifications.panel_label') }}"
    >
        <div class="VPNotificationBellHeader">
            <span>{{ __('vpress::notifications.panel_title') }}</span>
            <div class="VPNotificationBellHeaderActions">
                @if ($this->unreadCount > 0)
                    <button type="button" class="VPNotificationBellMarkAll" wire:click="markAllAsRead">
                        {{ __('vpress::notifications.mark_all_read') }}
                    </button>
                @endif
                @if ($this->items->isNotEmpty())
                    <button type="button" class="VPNotificationBellClearAll" wire:click="clearAll">
                        {{ __('vpress::notifications.clear_all') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="VPNotificationBellList">
            @forelse ($this->items as $item)
                <div class="VPNotificationBellItemWrap {{ $item['read'] ? 'is-read' : 'is-unread' }}">
                    @if (filled($item['url']))
                        <a
                            href="{{ $item['url'] }}"
                            class="VPNotificationBellItem"
                            role="menuitem"
                            wire:navigate="false"
                            wire:click="markAsRead('{{ $item['id'] }}')"
                        >
                            <span class="VPNotificationBellItemTitle">{{ $item['title'] }}</span>
                            <span class="VPNotificationBellItemBody">{{ $item['body'] }}</span>
                            <span class="VPNotificationBellItemTime">{{ $item['created_at'] }}</span>
                        </a>
                    @else
                        <button
                            type="button"
                            class="VPNotificationBellItem"
                            role="menuitem"
                            wire:click="markAsRead('{{ $item['id'] }}')"
                        >
                            <span class="VPNotificationBellItemTitle">{{ $item['title'] }}</span>
                            <span class="VPNotificationBellItemBody">{{ $item['body'] }}</span>
                            <span class="VPNotificationBellItemTime">{{ $item['created_at'] }}</span>
                        </button>
                    @endif
                    <button
                        type="button"
                        class="VPNotificationBellDelete"
                        aria-label="{{ __('vpress::notifications.delete') }}"
                        wire:click.stop="deleteNotification('{{ $item['id'] }}')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @empty
                <p class="VPNotificationBellEmpty">{{ __('vpress::notifications.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
