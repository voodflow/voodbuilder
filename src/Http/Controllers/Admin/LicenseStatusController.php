<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Licensing\EntitlementManager;
use Voodflow\Voodbuilder\Licensing\LicenseDashboardPayload;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * Stable JSON API for a future Filament licenses dashboard plugin.
 *
 * GET  /voodbuilder/admin/license/status
 * POST /voodbuilder/admin/license/refresh
 */
final class LicenseStatusController extends Controller
{
    public function status(EntitlementManager $manager): JsonResponse
    {
        $this->authorizeAdmin();

        return response()->json(LicenseDashboardPayload::make($manager));
    }

    public function refresh(EntitlementManager $manager): JsonResponse
    {
        $this->authorizeAdmin();

        LicenseDashboardPayload::forgetCachedSnapshots();

        // Force a fresh resolve (AnyStack refetch or config matrix).
        $manager->licenceStatus();
        $manager->capabilities();

        return response()->json([
            'refreshed' => true,
            'status' => LicenseDashboardPayload::make($manager),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
    }
}
