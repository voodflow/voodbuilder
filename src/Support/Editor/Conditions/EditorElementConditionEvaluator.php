<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Conditions;

use Illuminate\Support\Carbon;
use Voodflow\Voodbuilder\Models\SitePage;

final class EditorElementConditionEvaluator
{
    /**
     * @param  array{sets?: list<array{conditions?: list<array{key?: string, compare?: string, value?: mixed}>}>}  $definition
     */
    public function passes(array $definition, ?SitePage $page = null): bool
    {
        $sets = $definition['sets'] ?? [];

        if ($sets === []) {
            return true;
        }

        $matchAll = ($definition['match'] ?? 'any') === 'all';

        if ($matchAll) {
            foreach ($sets as $set) {
                if (! $this->setPasses($set, $page)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($sets as $set) {
            if ($this->setPasses($set, $page)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{conditions?: list<array{key?: string, compare?: string, value?: mixed}>}  $set
     */
    protected function setPasses(array $set, ?SitePage $page): bool
    {
        $conditions = $set['conditions'] ?? [];

        if ($conditions === []) {
            return false;
        }

        foreach ($conditions as $condition) {
            if (! $this->conditionPasses($condition, $page)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{key?: string, compare?: string, value?: mixed}  $condition
     */
    protected function conditionPasses(array $condition, ?SitePage $page): bool
    {
        $key = (string) ($condition['key'] ?? '');
        $compare = (string) ($condition['compare'] ?? '==');
        $value = $condition['value'] ?? '';

        $result = match ($key) {
            'user_logged_in' => $this->compareBool(auth()->check(), $compare, $this->truthy($value)),
            'user_role' => $this->compareRole($compare, (string) $value),
            'locale' => $this->compareString(app()->getLocale(), $compare, (string) $value),
            'route_name' => $this->compareString((string) request()->route()?->getName(), $compare, (string) $value),
            'page_path' => $this->compareString('/'.ltrim(request()->path(), '/'), $compare, '/'.ltrim((string) $value, '/')),
            'date_before' => $this->compareDateBefore((string) $value),
            'date_after' => $this->compareDateAfter((string) $value),
            default => EditorConditionHooks::evaluate($key, $condition, $page) ?? false,
        };

        return $result;
    }

    protected function compareBool(bool $actual, string $compare, bool $expected): bool
    {
        return match ($compare) {
            '!=', 'is_not' => $actual !== $expected,
            default => $actual === $expected,
        };
    }

    protected function compareRole(string $compare, string $role): bool
    {
        $user = auth()->user();

        if ($user === null || $role === '') {
            return false;
        }

        $hasRole = method_exists($user, 'hasRole')
            ? $user->hasRole($role)
            : false;

        return match ($compare) {
            '!=', 'is_not' => ! $hasRole,
            default => $hasRole,
        };
    }

    protected function compareString(string $actual, string $compare, string $expected): bool
    {
        return match ($compare) {
            '!=', 'is_not' => $actual !== $expected,
            'contains' => str_contains($actual, $expected),
            'not_contains' => ! str_contains($actual, $expected),
            default => $actual === $expected,
        };
    }

    protected function compareDateBefore(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        try {
            return now()->lt(Carbon::parse($value));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function compareDateAfter(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        try {
            return now()->gt(Carbon::parse($value));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'yes'], true);
    }
}
