<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Conditions;

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Voodflow\Voodbuilder\Models\SitePage;

final class EditorConditionHooks
{
    /** @var array<string, callable(array<string, mixed>, ?SitePage): bool> */
    private static array $handlers = [];

    /** @var array<string, callable(): list<array{value: string, label: string}>> */
    private static array $choiceProviders = [];

    /**
     * @param  callable(array<string, mixed>, ?SitePage): bool  $handler
     */
    public static function register(string $key, callable $handler): void
    {
        self::$handlers[$key] = $handler;
    }

    /**
     * @param  callable(): list<array{value: string, label: string}|array<string, string>>  $provider
     */
    public static function registerChoiceProvider(string $key, callable $provider): void
    {
        self::$choiceProviders[$key] = $provider;
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    public static function evaluate(string $key, array $condition, ?SitePage $page): ?bool
    {
        if (! isset(self::$handlers[$key])) {
            return null;
        }

        return (self::$handlers[$key])($condition, $page);
    }

    /**
     * @return list<array{key: string, label: string, compares: list<string>, value: array{type: string, choices?: list<array{value: string, label: string}>}}>
     */
    public static function options(): array
    {
        return array_map(
            fn (array $definition): array => self::withValueMeta($definition),
            self::definitions(),
        );
    }

    /**
     * @return list<array{key: string, label: string, compares: list<string>}>
     */
    private static function definitions(): array
    {
        return [
            ['key' => 'user_logged_in', 'label' => __('voodbuilder::pro.conditions.options.user_logged_in'), 'compares' => ['==', '!=']],
            ['key' => 'user_role', 'label' => __('voodbuilder::pro.conditions.options.user_role'), 'compares' => ['==', '!=']],
            ['key' => 'locale', 'label' => __('voodbuilder::pro.conditions.options.locale'), 'compares' => ['==', '!=']],
            ['key' => 'route_name', 'label' => __('voodbuilder::pro.conditions.options.route_name'), 'compares' => ['==', '!=', 'contains']],
            ['key' => 'date_before', 'label' => __('voodbuilder::pro.conditions.options.date_before'), 'compares' => []],
            ['key' => 'date_after', 'label' => __('voodbuilder::pro.conditions.options.date_after'), 'compares' => []],
        ];
    }

    /**
     * @param  array{key: string, label: string, compares: list<string>}  $definition
     * @return array{key: string, label: string, compares: list<string>, value: array{type: string, choices?: list<array{value: string, label: string}>}}
     */
    private static function withValueMeta(array $definition): array
    {
        $definition['value'] = self::valueMetaFor($definition['key']);

        return $definition;
    }

    /**
     * @return array{type: string, choices?: list<array{value: string, label: string}>}
     */
    private static function valueMetaFor(string $key): array
    {
        if (isset(self::$choiceProviders[$key])) {
            $choices = self::normalizeChoices((self::$choiceProviders[$key])());

            return [
                'type' => $choices === [] ? 'text' : 'select',
                'choices' => $choices,
            ];
        }

        $configured = config("voodbuilder.editor.conditions.value_meta.{$key}");

        if (is_array($configured) && isset($configured['type'])) {
            if (($configured['choices'] ?? null) !== null) {
                $configured['choices'] = self::normalizeChoices($configured['choices']);
            }

            return $configured;
        }

        return match ($key) {
            'user_logged_in' => ['type' => 'boolean'],
            'locale' => [
                'type' => 'select',
                'choices' => self::localeChoices(),
            ],
            'user_role' => self::roleValueMeta(),
            'route_name' => ['type' => 'text'],
            'date_before', 'date_after' => ['type' => 'date'],
            default => ['type' => 'text'],
        };
    }

    /**
     * @return array{type: string, choices?: list<array{value: string, label: string}>}
     */
    private static function roleValueMeta(): array
    {
        $choices = self::roleChoices();

        if ($choices === []) {
            return ['type' => 'text'];
        }

        return [
            'type' => 'select',
            'choices' => $choices,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private static function localeChoices(): array
    {
        $explicit = config('voodbuilder.editor.conditions.locales');

        if (is_array($explicit) && $explicit !== []) {
            return self::normalizeChoices($explicit);
        }

        foreach (['app.locales', 'vtuts.locales', 'vdocs.locales'] as $configKey) {
            $locales = config($configKey);

            if (is_array($locales) && $locales !== []) {
                return self::normalizeChoices($locales);
            }
        }

        $locale = app()->getLocale();

        return [
            ['value' => $locale, 'label' => strtoupper($locale)],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private static function roleChoices(): array
    {
        $explicit = config('voodbuilder.editor.conditions.roles');

        if (is_array($explicit) && $explicit !== []) {
            return self::normalizeChoices($explicit);
        }

        try {
            if (
                class_exists(Role::class)
                && Schema::hasTable('roles')
            ) {
                return Role::query()
                    ->orderBy('name')
                    ->pluck('name')
                    ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
                    ->values()
                    ->all();
            }
        } catch (\Throwable) {
            // Host app may not use Spatie Permission.
        }

        return [];
    }

    /**
     * @param  array<int|string, mixed>  $choices
     * @return list<array{value: string, label: string}>
     */
    private static function normalizeChoices(array $choices): array
    {
        $normalized = [];

        foreach ($choices as $key => $value) {
            if (is_array($value) && isset($value['value'])) {
                $normalized[] = [
                    'value' => (string) $value['value'],
                    'label' => (string) ($value['label'] ?? $value['value']),
                ];

                continue;
            }

            if (is_string($key)) {
                $normalized[] = [
                    'value' => $key,
                    'label' => is_string($value) ? $value : $key,
                ];
            }
        }

        return $normalized;
    }
}
