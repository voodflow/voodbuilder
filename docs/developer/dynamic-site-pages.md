# Dynamic SitePages

One Visual Editor **template** page can power many public URLs. Companion plugins keep their route patterns (1+n path parameters); a published dynamic `SitePage` **claims** those routes and wins over the legacy Blade view.

## Product rule

> If a published dynamic `SitePage` claims a companion route, **VoodBuilder renders that template**. Otherwise the companion Blade view is used.

Examples:

| Template claims | Public URLs |
|-----------------|-------------|
| `vexhibitors.show` | `/exhibitors/acme` |
| `vexhibitors.show-event` | `/exhibitors/acme/events/evens-2026` |
| both on the same page | same layout for both patterns |

## Admin (create / edit page)

Tab **Dynamic routing**:

1. Toggle **Dynamic page**
2. Choose **Model / channel** (from registered providers: exhibitors, events, sponsors, partners, …)
3. Check one or more **URL patterns (routes)** — each entry is a claimable companion route with its parameter pattern
4. Optional **priority** when multiple templates claim the same route

The page `slug` remains an admin/CMS identifier (and still works under `/pages/{slug}` for direct preview). Public entity URLs stay on the companion prefixes.

## Companion contract

Implement `Voodflow\Voodbuilder\Contracts\DynamicPageProvider` and register:

```php
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::dynamicPageProvider(new ExhibitorsDynamicPageProvider);
Voodbuilder::editorBindingSource(new ExhibitorCurrentEditorBindingSource);
```

### Provider responsibilities

| Method | Purpose |
|--------|---------|
| `channelId()` | Align with `contentChannel` id (`exhibitors`) |
| `claimableRoutes()` | `routeName => label` including path hints |
| `normalizeRouteName()` | Map locale-prefixed names (`vevents.en.show` → `vevents.show`) |
| `entityKeys()` | Bag keys companions pass (`exhibitor`, `event`) |
| `previewUrl()` / `previewEntities()` | Filament + editor binding preview |

### Controller hook

Keep companion routes registered. At the start of `show` / `showAtEvent` / `index` (optional):

```php
use Voodflow\Voodbuilder\Support\DynamicPages\RendersDynamicSitePage;

class ExhibitorController extends Controller
{
    use RendersDynamicSitePage;

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $exhibitor = $this->resolveExhibitor($slug);

        if ($view = $this->renderDynamicSitePage('exhibitors', 'vexhibitors.show', [
            'exhibitor' => $exhibitor,
        ])) {
            return $view;
        }

        // legacy Blade…
    }
}
```

### Binding source `.current`

Register an `EditorBindingSource` with id `{package}.current` that reads:

```php
$exhibitor = $context->routeEntity('exhibitor') ?? $context->routeItem;
```

`BindingContext` is filled from `DynamicPageRequestContext` during render (and from `previewEntities()` in the editor).

## Core pieces

| Class | Role |
|-------|------|
| `DynamicPageRegistry` | Providers |
| `DynamicPageResolver` | Find template + render `voodbuilder::pages.site-page` |
| `DynamicPageRequestContext` | Request-scoped entity bag |
| `BindingContext::$routeEntities` | Available to all binding sources |
| Migration `is_dynamic`, `dynamic_channel`, `dynamic_routes`, `dynamic_priority` | Storage |

## Multi-parameter URLs

Do **not** invent a second router for `{event}/{exhibitor}`. Claim the companion routes that already encode those segments. One template may claim several routes so both `/exhibitors/{slug}` and `/exhibitors/{slug}/events/{eventSlug}` share the same GrapesJS layout; blocks bind optional `event.*` fields when present.

## Soft gate

Core boots without companions. Filament channel/route selects are empty until providers register. Controllers soft-check via the trait / class_exists patterns already used for `Voodbuilder::contentChannel()`.

## Related

- [Companion integration](companion-integration.md)
- [Bindings](../BINDINGS.md)
- [Extending overview](../manual/developer/extending-overview.md)
