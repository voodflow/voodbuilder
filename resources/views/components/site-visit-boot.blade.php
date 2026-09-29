@props([
    'page' => null,
])

@php
    use Illuminate\Support\Facades\Route;
    use Voodflow\Voodbuilder\Models\SitePage;
    use Voodflow\Voodbuilder\Support\SiteVisit\SiteVisitWorkflowRunner;
    use Voodflow\Voodbuilder\Support\VoodflowIntegration;

    $isEditor = (bool) ($editorEditor ?? false)
        || (bool) ($chromeLayoutEditor ?? false)
        || request()->boolean('edit');

    $enabled = ! $isEditor
        && VoodflowIntegration::enabled()
        && Route::has('voodbuilder.visits.store');

    $resolvedPage = $page instanceof SitePage
        ? $page
        : (isset($page) && is_object($page) && method_exists($page, 'getKey') ? $page : null);

    // Anonymous Blade components do not inherit parent view data — layouts must pass :page.
    $pageId = $resolvedPage !== null
        ? (string) $resolvedPage->getKey()
        : null;
@endphp

@if ($enabled)
    <script type="application/json" data-voodbuilder-site-visit-config>
        {!! json_encode([
            'endpoint' => route('voodbuilder.visits.store', absolute: false),
            'visitorCookie' => SiteVisitWorkflowRunner::VISITOR_COOKIE,
            'pageId' => $pageId,
            'locale' => app()->getLocale(),
        ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
@endif
