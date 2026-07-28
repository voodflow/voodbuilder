<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsConditionHooks;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsConditionHooksLocaleChoicesTest extends TestCase
{
    public function test_it_prefers_explicit_voodbuilder_locales_over_host_and_legacy_configs(): void
    {
        config()->set('voodbuilder.grapesjs.conditions.locales', [
            'fr' => 'Francais',
            'de' => 'Deutsch',
        ]);
        config()->set('app.locales', [
            'en' => 'English',
            'it' => 'Italiano',
        ]);
        config()->set('vtuts.locales', [
            'es' => 'Espanol',
        ]);
        config()->set('vdocs.locales', [
            'pt' => 'Portugues',
        ]);

        $this->assertSame([
            ['value' => 'fr', 'label' => 'Francais'],
            ['value' => 'de', 'label' => 'Deutsch'],
        ], $this->localeChoices());
    }

    public function test_it_prefers_app_locales_over_legacy_package_locales(): void
    {
        config()->set('voodbuilder.grapesjs.conditions.locales', null);
        config()->set('app.locales', [
            'en' => 'English',
            'it' => 'Italiano',
        ]);
        config()->set('vtuts.locales', [
            'es' => 'Espanol',
        ]);
        config()->set('vdocs.locales', [
            'pt' => 'Portugues',
        ]);

        $this->assertSame([
            ['value' => 'en', 'label' => 'English'],
            ['value' => 'it', 'label' => 'Italiano'],
        ], $this->localeChoices());
    }

    public function test_it_falls_back_to_current_locale_when_no_configs_are_available(): void
    {
        app()->setLocale('it');

        config()->set('voodbuilder.grapesjs.conditions.locales', null);
        config()->set('app.locales', null);
        config()->set('vtuts.locales', null);
        config()->set('vdocs.locales', null);
        config()->set('cosmolab.locales', [
            'en' => 'English',
        ]);

        $this->assertSame([
            ['value' => 'it', 'label' => 'IT'],
        ], $this->localeChoices());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function localeChoices(): array
    {
        /** @var array{value: array{choices?: list<array{value: string, label: string}>}} $localeOption */
        $localeOption = Collection::make(GrapesJsConditionHooks::options())
            ->firstWhere('key', 'locale');

        return $localeOption['value']['choices'] ?? [];
    }
}
