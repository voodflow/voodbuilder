<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Services;

/**
 * Calls api.voodflow.com for the latest published voodbuilder tag (Anystack releases).
 *
 * @deprecated Prefer {@see RemotePackageVersionClient} for new call sites.
 */
final class VoodbuilderPortalClient
{
    public const LATEST_CACHE_KEY = RemotePackageVersionClient::CACHE_PREFIX.'portal.voodbuilder';

    public function getLatestPublishedTag(): ?string
    {
        return app(RemotePackageVersionClient::class)->latest('portal', 'voodbuilder');
    }

    public function forgetLatestCache(): void
    {
        app(RemotePackageVersionClient::class)->forget('portal', 'voodbuilder');
    }
}
