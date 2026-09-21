<?php

declare(strict_types=1);

return [
    'grace_active' => 'Using cached licence entitlements while the licensing service is unreachable.',
    'stale_cache_fail_open' => 'Licensing service still unreachable; keeping the last known entitlements so authoring and public pages stay available.',
    'flaky_community_ignored' => 'Licensing service returned Community while a paid edition was still cached; keeping the last known entitlements.',
    'expired' => 'Licence inactive or expired; authoring limited to Community. Published pages keep working.',
    'remote_unavailable' => 'Licensing service unavailable and no cached entitlements; Community authoring only. Public pages keep working.',
];
