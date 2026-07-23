<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\GrapesJsPopupHtmlNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\PopupRulesEvaluator;

class PopupsPublicController extends Controller
{
    public function __construct(
        private readonly PopupRulesEvaluator $rules,
    ) {}

    public function index(): JsonResponse
    {
        if (! config('voodbuilder.popups.enabled', true) || ! Schema::hasTable('voodbuilder_popups')) {
            return response()->json(['popups' => []]);
        }

        $popups = BuilderPopup::query()
            ->where('enabled', true)
            ->where('paused', false)
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get()
            ->filter(fn (BuilderPopup $popup): bool => $this->rules->matchesLocale($popup))
            ->filter(fn (BuilderPopup $popup): bool => $this->rules->passesTargeting($popup))
            ->filter(fn (BuilderPopup $popup): bool => $this->rules->isActiveNow($popup))
            ->filter(fn (BuilderPopup $popup): bool => filled($popup->html))
            ->map(fn (BuilderPopup $popup): array => [
                'id' => $popup->getKey(),
                'name' => $popup->name,
                'html' => GrapesJsPopupHtmlNormalizer::normalize((string) $popup->html),
                'css' => (string) ($popup->css ?? ''),
                'js' => (string) ($popup->js ?? ''),
                'rules' => $this->rules->publicRules($popup),
            ])
            ->values();

        return response()->json(['popups' => $popups]);
    }
}
