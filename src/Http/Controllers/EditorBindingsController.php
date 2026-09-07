<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * HTTP controller: Editor Bindings.
 */
class EditorBindingsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $registry = app(BindingRegistry::class);
        $offerRepeatItems = DynamicDataCollectionsBridge::authoringEnabled();

        return response()->json([
            'groups' => $this->withoutRepeatItemSources($registry->catalogGroupedByPackage(), $offerRepeatItems, 'sources'),
            'sources' => $this->withoutRepeatItemSources($registry->catalog(), $offerRepeatItems),
            // Empty when collections plugin is off / Community edition.
            'repeatSources' => DynamicDataCollectionsBridge::repeatSourcesCatalog(),
        ]);
    }

    /**
     * Hide `.item` sources from the picker unless the author may build repeats.
     *
     * The registry keeps them registered regardless, because published pages need them to
     * resolve. Offering them here without collections would be a dead end: an `.item`
     * binding only resolves inside a repeat, and the author has no way to create one.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    private function withoutRepeatItemSources(array $entries, bool $offerRepeatItems, ?string $nestedKey = null): array
    {
        if ($offerRepeatItems) {
            return $entries;
        }

        $kept = [];

        foreach ($entries as $entry) {
            if ($nestedKey !== null) {
                $nested = is_array($entry[$nestedKey] ?? null) ? $entry[$nestedKey] : [];
                $entry[$nestedKey] = $this->withoutRepeatItemSources(array_values($nested), false);

                if ($entry[$nestedKey] === []) {
                    continue;
                }

                $kept[] = $entry;

                continue;
            }

            if (str_ends_with((string) ($entry['id'] ?? ''), '.item')) {
                continue;
            }

            $kept[] = $entry;
        }

        return $kept;
    }
}
