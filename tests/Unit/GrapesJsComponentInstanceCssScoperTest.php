<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentInstanceCssScoper;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsComponentInstanceCssScoperTest extends TestCase
{
    public function test_scopes_pasted_component_utilities_to_component_instance(): void
    {
        $css = '.voodbuilder-pasted-component .max-w-2xl { max-width: var(--container-2xl); }';

        $scoped = GrapesJsComponentInstanceCssScoper::scopeCssToComponentInstance($css, 'cmp-1');

        $this->assertStringStartsWith('[data-vb-component-id="cmp-1"]', $scoped);
    }

    public function test_does_not_double_scope_css(): void
    {
        $css = '[data-vb-component-id="cmp-1"] .voodbuilder-pasted-component .max-w-2xl { max-width: 42rem; }';

        $scoped = GrapesJsComponentInstanceCssScoper::scopeCssToComponentInstance($css, 'cmp-1');

        $this->assertSame($css, $scoped);
    }

    public function test_scopes_theme_bridge_selectors(): void
    {
        $css = ':where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .text-white { color: #fff; }';

        $scoped = GrapesJsComponentInstanceCssScoper::scopeCssToComponentInstance($css, 'cmp-2');

        $this->assertStringContainsString('[data-vb-component-id="cmp-2"] .voodbuilder-pasted-component .text-white', $scoped);
    }
}
