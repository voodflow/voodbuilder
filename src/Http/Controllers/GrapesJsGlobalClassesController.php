<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\BuilderGlobalClass;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsCssSanitizer;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

class GrapesJsGlobalClassesController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        if (! Schema::hasTable('voodbuilder_global_classes')) {
            return response()->json(['classes' => []]);
        }

        $classes = BuilderGlobalClass::query()
            ->orderBy('name')
            ->get()
            ->map(fn (BuilderGlobalClass $class): array => $this->toArray($class));

        return response()->json(['classes' => $classes]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_-]*$/', 'unique:voodbuilder_global_classes,name'],
            'label' => ['required', 'string', 'max:120'],
            'css' => ['required', 'string', 'max:50000'],
        ]);

        $class = BuilderGlobalClass::query()->create([
            'name' => $validated['name'],
            'label' => $validated['label'],
            'css' => GrapesJsCssSanitizer::sanitize($validated['css']),
        ]);

        return response()->json(['class' => $this->toArray($class)], 201);
    }

    public function update(Request $request, BuilderGlobalClass $globalClass): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        abort_if($globalClass->locked, 422, 'This global class is locked.');

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:120'],
            'css' => ['sometimes', 'string', 'max:50000'],
            'locked' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['css'])) {
            $validated['css'] = GrapesJsCssSanitizer::sanitize($validated['css']);
        }

        $globalClass->update($validated);

        return response()->json(['class' => $this->toArray($globalClass->fresh())]);
    }

    public function destroy(BuilderGlobalClass $globalClass): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        abort_if($globalClass->locked, 422, 'This global class is locked.');

        $globalClass->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function toArray(BuilderGlobalClass $class): array
    {
        return [
            'id' => $class->id,
            'name' => $class->name,
            'label' => $class->label,
            'css' => $class->css,
            'locked' => $class->locked,
        ];
    }
}
