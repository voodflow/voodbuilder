<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Popups;

use Illuminate\Support\Carbon;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsElementConditionEvaluator;

final class PopupRulesEvaluator
{
    private const WEEKDAY_MAP = [
        'monday' => Carbon::MONDAY,
        'tuesday' => Carbon::TUESDAY,
        'wednesday' => Carbon::WEDNESDAY,
        'thursday' => Carbon::THURSDAY,
        'friday' => Carbon::FRIDAY,
        'saturday' => Carbon::SATURDAY,
        'sunday' => Carbon::SUNDAY,
    ];

    public function __construct(
        private readonly GrapesJsElementConditionEvaluator $conditions,
    ) {}

    public function passesTargeting(BuilderPopup $popup): bool
    {
        $targeting = $popup->normalizedRules()['targeting'] ?? [];
        $sets = $targeting['sets'] ?? [];

        if ($sets === []) {
            return true;
        }

        return $this->conditions->passes([
            'match' => $targeting['match'] ?? 'any',
            'sets' => $sets,
        ]);
    }

    /**
     * Empty / "*" locale means the popup is available for every site language.
     */
    public function matchesLocale(BuilderPopup $popup, ?string $locale = null): bool
    {
        $configured = strtolower(trim((string) ($popup->locale ?? '')));

        if ($configured === '' || $configured === '*') {
            return true;
        }

        $current = strtolower(trim($locale ?? app()->getLocale()));

        return $configured === $current;
    }

    public function isActiveNow(BuilderPopup $popup, ?Carbon $now = null): bool
    {
        $schedule = $popup->normalizedRules()['schedule'] ?? [];
        $timezone = $this->resolveTimezone($schedule['timezone'] ?? null);
        $now ??= Carbon::now($timezone);
        $now = $now->copy()->timezone($timezone);

        $startAt = $this->parseDateTime($schedule['start_at'] ?? null, $timezone);

        if ($startAt !== null && $now->lt($startAt)) {
            return false;
        }

        $endAt = $this->parseDateTime($schedule['end_at'] ?? null, $timezone);

        if ($endAt !== null && $now->gt($endAt)) {
            return false;
        }

        $weeklyDays = self::normalizeWeeklyDays($schedule);

        if ($weeklyDays === []) {
            return true;
        }

        $today = strtolower($now->englishDayOfWeek);

        if (! in_array($today, $weeklyDays, true)) {
            return false;
        }

        $startTime = $this->parseTime($schedule['weekly_start_time'] ?? null);
        $endTime = $this->parseTime($schedule['weekly_end_time'] ?? null);

        if ($startTime === null && $endTime === null) {
            return true;
        }

        $currentMinutes = ((int) $now->format('H') * 60) + (int) $now->format('i');

        if ($startTime !== null && $currentMinutes < $startTime) {
            return false;
        }

        if ($endTime !== null && $currentMinutes > $endTime) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $schedule
     * @return list<string>
     */
    public static function normalizeWeeklyDays(array $schedule): array
    {
        $days = $schedule['weekly_days'] ?? null;

        if (! is_array($days) || $days === []) {
            $legacy = strtolower(trim((string) ($schedule['weekly_day'] ?? '')));
            $days = $legacy !== '' ? [$legacy] : [];
        }

        $normalized = [];

        foreach ($days as $day) {
            $value = strtolower(trim((string) $day));

            if ($value !== '' && isset(self::WEEKDAY_MAP[$value]) && ! in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function publicRules(BuilderPopup $popup): array
    {
        $rules = $popup->normalizedRules();

        return [
            'trigger' => $rules['trigger'] ?? [],
            'frequency' => $rules['frequency'] ?? [],
            'display' => $rules['display'] ?? [],
        ];
    }

    private function resolveTimezone(mixed $timezone): string
    {
        $normalized = trim((string) ($timezone ?? ''));

        if ($normalized === '') {
            return (string) config('app.timezone', 'UTC');
        }

        try {
            new \DateTimeZone($normalized);

            return $normalized;
        } catch (\Throwable) {
            return (string) config('app.timezone', 'UTC');
        }
    }

    private function parseDateTime(mixed $value, string $timezone): ?Carbon
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '') {
            return null;
        }

        try {
            return Carbon::parse($normalized, $timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTime(mixed $value): ?int
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '' || ! preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})$/', $normalized, $matches)) {
            return null;
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return ($hour * 60) + $minute;
    }
}
