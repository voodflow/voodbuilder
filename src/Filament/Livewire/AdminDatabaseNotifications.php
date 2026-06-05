<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Livewire;

use Filament\Notifications\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;
use Relaticle\Comments\Notifications\CommentRepliedNotification;
use Relaticle\Comments\Notifications\UserMentionedNotification;
use Voodflow\Vpress\Support\SiteNotificationPresenter;

class AdminDatabaseNotifications extends BaseDatabaseNotifications
{
    public function getNotificationsQuery(): Builder | Relation
    {
        $user = $this->getUser();

        if (! $user) {
            abort(401);
        }

        /** @phpstan-ignore-next-line */
        return $user->notifications();
    }

    public function getNotification(DatabaseNotification $databaseNotification): Notification
    {
        if (in_array($databaseNotification->type, [
            CommentRepliedNotification::class,
            UserMentionedNotification::class,
        ], true)) {
            $presented = SiteNotificationPresenter::present($databaseNotification);

            $filamentNotification = Notification::make()
                ->title($presented['title'])
                ->body($presented['body'])
                ->id($databaseNotification->getKey());

            if (filled($presented['url'])) {
                $filamentNotification->actions([
                    \Filament\Actions\Action::make('view')
                        ->label(__('vpress::notifications.view'))
                        ->url($presented['url'])
                        ->openUrlInNewTab(),
                ]);
            }

            return $filamentNotification
                ->date($this->formatNotificationDate($databaseNotification->getAttributeValue('created_at')));
        }

        return parent::getNotification($databaseNotification)
            ->date($this->formatNotificationDate($databaseNotification->getAttributeValue('created_at')));
    }
}
