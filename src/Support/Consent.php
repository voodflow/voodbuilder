<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Closure;
use Voodflow\Voodbuilder\Contracts\ConsentGate;

/**
 * Consent registry: the extension point a cookie-consent package plugs into.
 *
 * No gate registered — or no consent package installed (see
 * `config('voodbuilder.integrations.consent_gate')`) — means “nothing is gated”, so
 * a site without a cookie bar keeps working. When several gates are registered every
 * one of them must allow the category.
 */
final class Consent
{
    /** @var array<int, ConsentGate|Closure> */
    private static array $gates = [];

    public static function register(ConsentGate | Closure $gate): void
    {
        self::$gates[] = $gate;
    }

    public static function reset(): void
    {
        self::$gates = [];
    }

    /**
     * Whether the given category is allowed. Gates may be registered by a companion
     * package (preferred) or discovered from the configured class name.
     */
    public static function allows(string $category): bool
    {
        $gates = self::$gates;

        $configured = self::configuredGate();
        if ($configured !== null) {
            $gates[] = $configured;
        }

        if ($gates === []) {
            return true;
        }

        foreach ($gates as $gate) {
            $allowed = $gate instanceof ConsentGate
                ? $gate->allows($category)
                : (bool) $gate($category);

            if (! $allowed) {
                return false;
            }
        }

        return true;
    }

    /**
     * True when something actually answers consent questions, so callers can skip
     * consent-only UI (e.g. the consent preference links) on an unguarded site.
     */
    public static function isGuarded(): bool
    {
        return self::$gates !== [] || self::configuredGate() !== null;
    }

    /**
     * Decoded visitor consent payload, when the configured provider exposes one.
     *
     * @return array<string, mixed>|null
     */
    public static function preferences(): ?array
    {
        $class = config('voodbuilder.integrations.consent_payload_class');

        if (! is_string($class) || $class === '' || ! class_exists($class) || ! method_exists($class, 'decode')) {
            return null;
        }

        $cookieName = (string) config('voodbuilder.integrations.consent_cookie', 'vcookiebar_consent');
        $decoded = $class::decode(request()->cookie($cookieName));

        if (is_array($decoded)) {
            return $decoded;
        }

        if (is_object($decoded) && method_exists($decoded, 'toArray')) {
            return (array) $decoded->toArray();
        }

        return null;
    }

    private static function configuredGate(): ?Closure
    {
        $class = config('voodbuilder.integrations.consent_gate');

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return null;
        }

        $method = (string) config('voodbuilder.integrations.consent_method', 'allows');

        if (! method_exists($class, $method)) {
            return null;
        }

        return static fn (string $category): bool => (bool) $class::$method($category);
    }
}
