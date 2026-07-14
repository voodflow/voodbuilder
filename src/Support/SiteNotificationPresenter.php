<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Relaticle\Comments\Notifications\CommentRepliedNotification;
use Relaticle\Comments\Notifications\UserMentionedNotification;
use Voodflow\Vtuts\Models\Vtut;
use Voodflow\Vtuts\Support\VtutUrls;

final class SiteNotificationPresenter
{
    /**
     * @return array{title: string, body: string, url: ?string, icon: string}
     */
    public static function present(DatabaseNotification $notification): array
    {
        $type = $notification->type;
        $data = $notification->data;

        return match ($type) {
            CommentRepliedNotification::class => self::presentCommentNotification(
                title: __('voodbuilder::notifications.reply_title'),
                message: __('voodbuilder::notifications.reply_body', [
                    'name' => $data['commenter_name'] ?? __('voodbuilder::notifications.someone'),
                    'excerpt' => self::plainExcerpt($data['body'] ?? ''),
                ]),
                data: $data,
                icon: 'reply',
            ),
            UserMentionedNotification::class => self::presentCommentNotification(
                title: __('voodbuilder::notifications.mention_title'),
                message: __('voodbuilder::notifications.mention_body', [
                    'name' => $data['mentioner_name'] ?? __('voodbuilder::notifications.someone'),
                    'excerpt' => self::plainExcerpt($data['body'] ?? ''),
                ]),
                data: $data,
                icon: 'mention',
            ),
            default => self::presentDefault($notification),
        };
    }

    /**
     * @return array{title: string, body: string, url: ?string, icon: string}
     */
    protected static function presentDefault(DatabaseNotification $notification): array
    {
        if (self::isFilamentNotification($notification)) {
            return self::presentFilamentNotification($notification);
        }

        $data = $notification->data;

        return [
            'title' => __('voodbuilder::notifications.generic_title'),
            'body' => Str::limit(is_array($data)
                ? (string) ($data['message'] ?? $data['body'] ?? json_encode($data))
                : (string) $data, 240),
            'url' => null,
            'icon' => 'bell',
        ];
    }

    protected static function isFilamentNotification(DatabaseNotification $notification): bool
    {
        if ($notification->type === FilamentDatabaseNotification::class) {
            return true;
        }

        $data = $notification->data;

        return is_array($data) && ($data['format'] ?? null) === 'filament';
    }

    /**
     * @return array{title: string, body: string, url: ?string, icon: string}
     */
    protected static function presentFilamentNotification(DatabaseNotification $notification): array
    {
        try {
            $parsed = FilamentNotification::fromDatabase($notification);

            return [
                'title' => filled($parsed->getTitle())
                    ? (string) $parsed->getTitle()
                    : __('voodbuilder::notifications.generic_title'),
                'body' => (string) ($parsed->getBody() ?? ''),
                'url' => null,
                'icon' => 'bell',
            ];
        } catch (\Throwable) {
            $data = $notification->data;

            return [
                'title' => is_array($data)
                    ? (string) ($data['title'] ?? __('voodbuilder::notifications.generic_title'))
                    : __('voodbuilder::notifications.generic_title'),
                'body' => is_array($data) ? (string) ($data['body'] ?? '') : '',
                'url' => null,
                'icon' => 'bell',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, body: string, url: ?string, icon: string}
     */
    protected static function presentCommentNotification(string $title, string $message, array $data, string $icon): array
    {
        $context = self::commentableTitle($data);

        return [
            'title' => $title,
            'body' => $context
                ? $message.' '.__('voodbuilder::notifications.on_tutorial', ['title' => $context])
                : $message,
            'url' => self::commentUrl($data),
            'icon' => $icon,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function commentUrl(array $data): ?string
    {
        $commentableType = $data['commentable_type'] ?? null;
        $commentableId = $data['commentable_id'] ?? null;
        $commentId = $data['comment_id'] ?? null;

        $commentable = self::resolveCommentable($commentableType, $commentableId);

        if ($commentable === null) {
            return null;
        }

        $baseUrl = self::commentableUrl($commentable);

        if ($baseUrl === null) {
            return null;
        }

        if (filled($commentId)) {
            return $baseUrl.'#comment-'.$commentId;
        }

        return $baseUrl.'#tutorial-comments';
    }

    protected static function commentableUrl(Model $commentable): ?string
    {
        if ($commentable instanceof Vtut && class_exists(VtutUrls::class)) {
            return VtutUrls::show($commentable);
        }

        if (method_exists($commentable, 'getUrl')) {
            return $commentable->getUrl();
        }

        return null;
    }

    public static function commentableTitle(array $data): ?string
    {
        $commentableType = $data['commentable_type'] ?? null;
        $commentableId = $data['commentable_id'] ?? null;

        $commentable = self::resolveCommentable($commentableType, $commentableId);

        if ($commentable === null) {
            return null;
        }

        return match (true) {
            $commentable instanceof Vtut => $commentable->title,
            method_exists($commentable, 'getAttribute') => $commentable->getAttribute('title'),
            default => null,
        };
    }

    public static function plainExcerpt(mixed $value): string
    {
        if (! is_string($value) || blank($value)) {
            return '';
        }

        $text = strip_tags($value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::squish($text);
    }

    protected static function resolveCommentable(mixed $commentableType, mixed $commentableId): ?Model
    {
        if (! is_string($commentableType) || blank($commentableId)) {
            return null;
        }

        $modelClass = Relation::getMorphedModel($commentableType) ?? $commentableType;

        if (! class_exists($modelClass)) {
            return null;
        }

        /** @var Model|null */
        return $modelClass::query()->find($commentableId);
    }
}
