<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Livewire;

use Filament\Actions\Action;
use Filament\Livewire\DatabaseNotifications as PanelDatabaseNotifications;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;
use Relaticle\Comments\Notifications\CommentRepliedNotification;
use Relaticle\Comments\Notifications\UserMentionedNotification;
use Voodflow\Voodbuilder\Support\SiteNotificationPresenter;

/**
 * Admin panel database notifications.
 *
 * Extends Filament's panel Livewire component (not the base notifications one)
 * so the topbar/sidebar bell trigger is rendered.
 */
class AdminDatabaseNotifications extends PanelDatabaseNotifications
{
    public function getNotificationsQuery(): Builder|Relation
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
                    Action::make('view')
                        ->label(__('voodbuilder::notifications.view'))
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
