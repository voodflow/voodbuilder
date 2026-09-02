<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Support\Editor\EditorJsSanitizer;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * The one place that decides whether author-written JavaScript may exist and run.
 *
 * This is the deliberate exception to the rule that entitlements govern authoring and
 * never the rendering of published content (see
 * {@see DynamicDataCollectionsBridge::renderingEnabled()}).
 *
 * The reason it is an exception is that this channel is not content. Everything else the
 * builder stores is markup that a sanitizer has already reduced to something inert, so
 * rendering it is safe no matter how the licence resolves. A `<script>` block is arbitrary
 * code with the visitor's session in scope, and {@see EditorJsSanitizer}
 * is a deny-list — it blocks `eval` and `fetch`, not the rest of the DOM API. Running
 * arbitrary code because a licence check failed open is a worse outcome than not running it.
 *
 * It is also allowed to be strict because it fails *safe* rather than destructively: the
 * page renders in full, it just renders without the enhancement script. No section
 * disappears, no list empties, nothing 500s. That is the line — billing state may withhold
 * an enhancement, it may not unpublish content.
 *
 * Enforced on both sides on purpose. At write time so an installation that cannot hold the
 * capability cannot accumulate scripts it would never be allowed to run, and at render time
 * so historical rows — seeded pages, restored revisions, imported templates, direct
 * database writes — cannot smuggle a script past the check either.
 */
final class AuthorScriptPolicy
{
    public const CAPABILITY = 'pages.custom-js';

    public static function allowed(): bool
    {
        return Voodbuilder::can(self::CAPABILITY);
    }

    /**
     * Return the script only where it is permitted, otherwise an empty string.
     */
    public static function keepOrDiscard(?string $js): string
    {
        if (! filled($js) || ! self::allowed()) {
            return '';
        }

        return (string) $js;
    }
}
