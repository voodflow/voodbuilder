<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SitePageAccess;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SitePageViewData;
use Voodflow\Vtuts\Support\Locales;

/**
 * Resolve a published dynamic SitePage template and render it instead of Blade.
 */
final class DynamicPageResolver
{
    /**
     * @param  array<string, Model|null>  $entities
     */
    public static function maybeRender(
        string $channel,
        string $routeName,
        array $entities = [],
        ?Request $request = null,
    ): ?View {
        $page = self::findTemplate($channel, $routeName);

        if ($page === null) {
            return null;
        }

        $request ??= request();

        if (! PageBuilderAccess::userCanUsePageBuilder() && ! $page->published) {
            return null;
        }

        app()->setLocale($page->locale);

        DynamicPageRequestContext::bind($page, $channel, $entities);

        $seoSubject = DynamicPageSeo::subject($page, $entities);

        if (method_exists($seoSubject, 'seo')) {
            $seoSubject->loadMissing('seo');
        }

        seo()->for($seoSubject);

        $page->loadMissing('credentials');

        $gate = SitePageAccess::denyReason($page, $request);

        if ($gate !== null) {
            session()->put('url.intended', $request->url());
        }

        return view('voodbuilder::pages.site-page', SitePageViewData::make($page, [
            'pageGate' => $gate,
            'pageGateOfferPassword' => SitePageAccess::offerPasswordBypass($page, $gate),
            'pageGateRequiresEmail' => SitePageAccess::offerPasswordBypass($page, $gate)
                ? SitePageAccess::requiresEmailField($page)
                : false,
            'pageGateLoginUrl' => SitePageAccess::loginUrl(),
            'pageGateRegisterUrl' => SitePageAccess::registerUrl(),
            'pageGateSubscribeUrl' => SitePageAccess::subscribeUrl(),
            'dynamicChannel' => $channel,
            'dynamicEntities' => $entities,
        ]));
    }

    public static function findTemplate(string $channel, string $routeName, ?string $locale = null): ?SitePage
    {
        if (! self::schemaReady()) {
            return null;
        }

        $provider = app(DynamicPageRegistry::class)->get($channel);

        if ($provider === null) {
            return null;
        }

        $logicalRoute = $provider->normalizeRouteName($routeName);
        $locale ??= SitePageResolver::preferredLocale();

        $query = SitePage::query()
            ->where('is_dynamic', true)
            ->where('dynamic_channel', $channel)
            ->where('locale', $locale)
            ->orderByDesc('dynamic_priority')
            ->orderByDesc('id');

        if (! PageBuilderAccess::userCanUsePageBuilder()) {
            $query->where('published', true);
        }

        /** @var list<SitePage> $candidates */
        $candidates = $query->get()->all();

        foreach ($candidates as $page) {
            $routes = $page->dynamicRouteNames();

            if (in_array($logicalRoute, $routes, true) || in_array($routeName, $routes, true)) {
                return $page;
            }
        }

        if (class_exists(Locales::class) && $locale !== Locales::default()) {
            return self::findTemplate($channel, $routeName, Locales::default());
        }

        return null;
    }

    public static function schemaReady(): bool
    {
        try {
            return Schema::hasTable('site_pages')
                && Schema::hasColumn('site_pages', 'is_dynamic');
        } catch (\Throwable) {
            return false;
        }
    }
}
