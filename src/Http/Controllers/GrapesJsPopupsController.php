<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\GrapesJsPopupHtmlNormalizer;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

class GrapesJsPopupsController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        if (! Schema::hasTable('voodbuilder_popups')) {
            return response()->json(['popups' => []]);
        }

        $popups = BuilderPopup::query()
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get()
            ->map(fn (BuilderPopup $popup): array => $this->toArray($popup));

        return response()->json(['popups' => $popups]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'locale' => ['nullable', 'string', 'max:12'],
            'enabled' => ['nullable', 'boolean'],
            'paused' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'rules' => ['nullable', 'array'],
        ]);

        $data = PopupResource::normalizeFormData([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'locale' => $validated['locale'] ?? null,
            'enabled' => $validated['enabled'] ?? true,
            'paused' => $validated['paused'] ?? false,
            'priority' => $validated['priority'] ?? 0,
            'rules' => $validated['rules'] ?? BuilderPopup::defaultRules(),
        ]);

        $popup = BuilderPopup::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'locale' => $data['locale'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? true),
            'paused' => (bool) ($data['paused'] ?? false),
            'priority' => (int) ($data['priority'] ?? 0),
            'rules' => $data['rules'],
            'html' => $this->defaultHtml((string) $data['name']),
        ]);

        return response()->json(['popup' => $this->toArray($popup)], 201);
    }

    public function update(Request $request, BuilderPopup $popup): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:12'],
            'enabled' => ['sometimes', 'boolean'],
            'paused' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'rules' => ['sometimes', 'array'],
        ]);

        $data = PopupResource::normalizeFormData(PopupResource::mergeEditorPayload(
            [
                'name' => $popup->name,
                'description' => $popup->description,
                'locale' => $popup->locale,
                'enabled' => $popup->enabled,
                'paused' => $popup->paused,
                'priority' => $popup->priority,
                'rules' => $popup->normalizedRules(),
            ],
            $validated,
        ));

        $popup->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'locale' => $data['locale'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? true),
            'paused' => (bool) ($data['paused'] ?? false),
            'priority' => (int) ($data['priority'] ?? 0),
            'rules' => $data['rules'],
        ]);

        return response()->json(['popup' => $this->toArray($popup->fresh())]);
    }

    public function destroy(BuilderPopup $popup): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $popup->delete();

        return response()->json(['deleted' => true]);
    }

    public function pagePaths(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        return response()->json([
            'paths' => \Voodflow\Voodbuilder\Support\GrapesJs\Popups\PopupPagePathOptions::all(),
        ]);
    }

    protected function defaultHtml(string $name): string
    {
        $title = e($name);
        $body = e(__('voodbuilder::popups.defaults.body'));
        $cta = e(__('voodbuilder::popups.defaults.cta'));

        return '<section class="voodbuilder-gjs-section bg-vp-bg"><div class="voodbuilder-gjs-container px-6 py-10"><div class="rounded-xl border border-vp-divider bg-vp-bg-elv p-8 text-center"><h2 class="text-2xl font-semibold text-vp-text-1 mb-3">'.$title.'</h2><p class="text-vp-text-2 mb-6">'.$body.'</p><button type="button" class="inline-flex items-center rounded-lg bg-vp-brand-1 px-4 py-2 text-sm font-medium text-white" data-voodbuilder-popup-close>'.$cta.'</button></div></div></section>';
    }

    /**
     * @return array<string, mixed>
     */
    protected function toArray(BuilderPopup $popup): array
    {
        $hydrated = PopupResource::hydrateFormData([
            'id' => $popup->getKey(),
            'name' => $popup->name,
            'description' => $popup->description,
            'locale' => $popup->locale,
            'enabled' => $popup->enabled,
            'paused' => $popup->paused,
            'priority' => $popup->priority,
            'rules' => $popup->normalizedRules(),
            'updated_at' => $popup->updated_at?->toIso8601String(),
        ]);

        return [
            'id' => $popup->getKey(),
            'name' => $popup->name,
            'description' => $popup->description,
            'locale' => $popup->locale,
            'enabled' => $popup->enabled,
            'paused' => $popup->paused,
            'priority' => $popup->priority,
            'rules' => $hydrated['rules'],
            'html' => GrapesJsPopupHtmlNormalizer::normalize((string) $popup->html),
            'css' => (string) ($popup->css ?? ''),
            'js' => (string) ($popup->js ?? ''),
            'editor_url' => route('voodbuilder.popups.editor', ['popup' => $popup, 'edit' => 1]),
            'updated_at' => $popup->updated_at?->toIso8601String(),
        ];
    }
}
