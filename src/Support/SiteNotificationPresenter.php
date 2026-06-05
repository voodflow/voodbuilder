<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Relaticle\Comments\Notifications\CommentRepliedNotification;
use Relaticle\Comments\Notifications\UserMentionedNotification;
use Voodflow\Tutorials\Models\Tutorial;
use Voodflow\Tutorials\Support\TutorialUrls;

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
            CommentRepliedNotification::class => static::presentCommentNotification(
                title: __('vpress::notifications.reply_title'),
                message: __('vpress::notifications.reply_body', [
                    'name' => $data['commenter_name'] ?? __('vpress::notifications.someone'),
                    'excerpt' => static::plainExcerpt($data['body'] ?? ''),
                ]),
                data: $data,
                icon: 'reply',
            ),
            UserMentionedNotification::class => static::presentCommentNotification(
                title: __('vpress::notifications.mention_title'),
                message: __('vpress::notifications.mention_body', [
                    'name' => $data['mentioner_name'] ?? __('vpress::notifications.someone'),
                    'excerpt' => static::plainExcerpt($data['body'] ?? ''),
                ]),
                data: $data,
                icon: 'mention',
            ),
            default => [
                'title' => __('vpress::notifications.generic_title'),
                'body' => Str::limit((string) json_encode($data), 120),
                'url' => null,
                'icon' => 'bell',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, body: string, url: ?string, icon: string}
     */
    protected static function presentCommentNotification(string $title, string $message, array $data, string $icon): array
    {
        $context = static::commentableTitle($data);

        return [
            'title' => $title,
            'body' => $context
                ? $message.' '.__('vpress::notifications.on_tutorial', ['title' => $context])
                : $message,
            'url' => static::commentUrl($data),
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

        $commentable = static::resolveCommentable($commentableType, $commentableId);

        if ($commentable === null) {
            return null;
        }

        $baseUrl = static::commentableUrl($commentable);

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
        if ($commentable instanceof Tutorial && class_exists(TutorialUrls::class)) {
            return TutorialUrls::show($commentable);
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

        $commentable = static::resolveCommentable($commentableType, $commentableId);

        if ($commentable === null) {
            return null;
        }

        return match (true) {
            $commentable instanceof Tutorial => $commentable->title,
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
