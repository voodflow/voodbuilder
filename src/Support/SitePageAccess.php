<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Enums\PageVisibility;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageCredential;
use Voodflow\Vtuts\Support\SubscriberAccess;

/**
 * Page-level visibility + password gate for public site pages.
 *
 * Access is granted when either:
 * - the visitor meets the visibility level (and password if also required), or
 * - they unlock with a page password (bypass — preview / invite / one-off access).
 *
 * Editors with page-builder access bypass both gates.
 */
final class SitePageAccess
{
    public const REASON_REGISTERED = 'registered';

    public const REASON_SUBSCRIBER = 'subscriber';

    public const REASON_PASSWORD = 'password';

    public static function resolveVisibility(mixed $raw): PageVisibility
    {
        if ($raw instanceof PageVisibility) {
            return $raw;
        }

        if (is_string($raw)) {
            return PageVisibility::tryFrom($raw) ?? PageVisibility::Public;
        }

        return PageVisibility::Public;
    }

    /**
     * Whether the visitor already satisfies the page visibility level
     * (ignoring password protection).
     */
    public static function meetsVisibility(SitePage $page, ?Request $request = null): bool
    {
        $request ??= request();
        $visibility = self::resolveVisibility($page->visibility ?? PageVisibility::Public);
        $user = $request->user();

        return match ($visibility) {
            PageVisibility::Public => true,
            PageVisibility::Registered => $user !== null,
            PageVisibility::Subscriber => self::canAccessSubscriberContent($user),
        };
    }

    /**
     * Primary gate reason for the paywall card, or null when content may be shown.
     *
     * @return self::REASON_*|null
     */
    public static function denyReason(SitePage $page, ?Request $request = null): ?string
    {
        $request ??= request();

        if (PageBuilderAccess::userCanUsePageBuilder()) {
            return null;
        }

        // Password unlock is an alternate path (preview / invite), not subordinate
        // to visibility — once unlocked, show content regardless of role.
        if ($page->password_protected && self::isUnlocked($page, $request)) {
            return null;
        }

        if (! self::meetsVisibility($page, $request)) {
            $visibility = self::resolveVisibility($page->visibility ?? PageVisibility::Public);

            return match ($visibility) {
                PageVisibility::Registered => self::REASON_REGISTERED,
                PageVisibility::Subscriber => self::REASON_SUBSCRIBER,
                default => self::REASON_REGISTERED,
            };
        }

        if ($page->password_protected) {
            return self::REASON_PASSWORD;
        }

        return null;
    }

    /**
     * Show the password form on membership gates so invited users can unlock
     * without becoming a subscriber / logging in.
     */
    public static function offerPasswordBypass(SitePage $page, ?string $gate): bool
    {
        if (! $page->password_protected || $gate === null) {
            return false;
        }

        return in_array($gate, [self::REASON_REGISTERED, self::REASON_SUBSCRIBER, self::REASON_PASSWORD], true);
    }

    public static function isUnlocked(SitePage $page, ?Request $request = null): bool
    {
        $request ??= request();
        $key = self::sessionKey($page);
        $value = $request->session()->get($key);

        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }

        if (! is_numeric($value)) {
            return false;
        }

        $expiresAt = (int) $value;

        if ($expiresAt < time()) {
            $request->session()->forget($key);

            return false;
        }

        return true;
    }

    public static function unlock(SitePage $page, Request $request): void
    {
        $minutes = config('voodbuilder.access.password_unlock_minutes');

        if (is_numeric($minutes) && (int) $minutes > 0) {
            $request->session()->put(
                self::sessionKey($page),
                time() + ((int) $minutes * 60),
            );

            return;
        }

        $request->session()->put(self::sessionKey($page), true);
    }

    public static function attemptUnlock(SitePage $page, Request $request, ?string $email, string $password): bool
    {
        $credentials = $page->relationLoaded('credentials')
            ? $page->credentials
            : $page->credentials()->get();

        if ($credentials->isEmpty()) {
            return false;
        }

        $email = filled($email) ? mb_strtolower(trim((string) $email)) : null;

        foreach ($credentials as $credential) {
            if (! $credential instanceof SitePageCredential) {
                continue;
            }

            if (! Hash::check($password, $credential->password)) {
                continue;
            }

            $credentialEmail = filled($credential->email)
                ? mb_strtolower(trim((string) $credential->email))
                : null;

            if ($credentialEmail === null) {
                self::unlock($page, $request);

                return true;
            }

            if ($email !== null && hash_equals($credentialEmail, $email)) {
                self::unlock($page, $request);

                return true;
            }
        }

        return false;
    }

    public static function requiresEmailField(SitePage $page): bool
    {
        $credentials = $page->relationLoaded('credentials')
            ? $page->credentials
            : $page->credentials()->get();

        return $credentials->contains(fn (SitePageCredential $credential): bool => filled($credential->email));
    }

    public static function sessionKey(SitePage $page): string
    {
        $group = filled($page->translation_group_id)
            ? (string) $page->translation_group_id
            : 'page-'.$page->getKey();

        return 'voodbuilder.page_unlock.'.$group;
    }

    public static function canAccessSubscriberContent(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (class_exists(SubscriberAccess::class)) {
            return SubscriberAccess::canAccess($user);
        }

        if (! method_exists($user, 'can')) {
            return false;
        }

        $ability = (string) config(
            'voodbuilder.access.subscriber_ability',
            'access subscriber content',
        );

        return $user->can($ability);
    }

    public static function loginUrl(): string
    {
        $route = (string) config('voodbuilder.access.login_route', 'login');

        if (Route::has($route)) {
            return route($route);
        }

        return url('/login');
    }

    public static function registerUrl(): ?string
    {
        $route = config('voodbuilder.access.register_route', 'register');

        if (! is_string($route) || $route === '' || ! Route::has($route)) {
            return null;
        }

        return route($route);
    }

    public static function subscribeUrl(): ?string
    {
        $url = config('voodbuilder.access.subscribe_url');

        if (is_callable($url)) {
            $url = $url();
        }

        if (is_string($url) && $url !== '') {
            return $url;
        }

        if (class_exists(SubscriberAccess::class)
            && class_exists(\Voodflow\Vtuts\Support\SubKit::class)
            && \Voodflow\Vtuts\Support\SubKit::isEnabled()
            && Route::has(\Voodflow\Vtuts\Support\SubKit::pricingRouteName())) {
            return route(\Voodflow\Vtuts\Support\SubKit::pricingRouteName());
        }

        return null;
    }
}
