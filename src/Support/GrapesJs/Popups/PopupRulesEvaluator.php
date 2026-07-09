<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Popups;

use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsElementConditionEvaluator;

final class PopupRulesEvaluator
{
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
}
