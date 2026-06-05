<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Component;
use Voodflow\Vpress\Support\SiteNotificationPresenter;

class SiteNotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function markAsRead(string $id): void
    {
        $this->notificationQuery()
            ->where('id', $id)
            ->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        $this->unreadQuery()->update(['read_at' => now()]);
    }

    public function clearAll(): void
    {
        $this->notificationQuery()->delete();
        $this->open = false;
    }

    public function deleteNotification(string $id): void
    {
        $this->notificationQuery()
            ->where('id', $id)
            ->delete();
    }

    public function getUnreadCountProperty(): int
    {
        return $this->unreadQuery()->count();
    }

    /**
     * @return Collection<int, array{id: string, title: string, body: string, url: ?string, icon: string, read: bool, created_at: string}>
     */
    public function getItemsProperty(): Collection
    {
        return $this->notificationQuery()
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                $presented = SiteNotificationPresenter::present($notification);

                return [
                    'id' => $notification->id,
                    'title' => $presented['title'],
                    'body' => $presented['body'],
                    'url' => $presented['url'],
                    'icon' => $presented['icon'],
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->diffForHumans() ?? '',
                ];
            });
    }

    public function render(): View
    {
        return view('vpress::livewire.site-notification-bell');
    }

    protected function notificationQuery()
    {
        $user = auth()->user();

        if ($user === null) {
            abort(401);
        }

        return $user->notifications();
    }

    protected function unreadQuery()
    {
        return $this->notificationQuery()->whereNull('read_at');
    }
}
