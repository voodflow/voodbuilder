<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Services\RemotePackageVersionClient;
use Voodflow\Voodbuilder\Services\VoodbuilderPortalClient;
use Voodflow\Voodbuilder\Support\VoodbuilderApiEndpoints;
use Voodflow\Voodbuilder\Tests\TestCase;

final class VoodbuilderPortalClientTest extends TestCase
{
    public function test_fetches_latest_tag_from_api(): void
    {
        Cache::forget(VoodbuilderPortalClient::LATEST_CACHE_KEY);
        app(RemotePackageVersionClient::class)->forget('portal', 'voodbuilder');

        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response(['tag' => '0.1.99'], 200),
        ]);

        $tag = app(VoodbuilderPortalClient::class)->getLatestPublishedTag();

        $this->assertSame('0.1.99', $tag);
        Http::assertSentCount(1);
    }

    public function test_returns_null_when_api_unavailable(): void
    {
        Cache::forget(VoodbuilderPortalClient::LATEST_CACHE_KEY);
        app(RemotePackageVersionClient::class)->forget('portal', 'voodbuilder');

        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response('error', 503),
        ]);

        $this->assertNull(app(VoodbuilderPortalClient::class)->getLatestPublishedTag());
    }
}
