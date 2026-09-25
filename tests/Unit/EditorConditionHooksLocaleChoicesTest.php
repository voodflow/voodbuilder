<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionHooks;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorConditionHooksLocaleChoicesTest extends TestCase
{
    public function test_it_uses_host_app_locales_and_ignores_package_configs(): void
    {
        config()->set('app.locales', [
            'en' => 'English',
            'it' => 'Italiano',
        ]);
        config()->set('voodbuilder.editor.conditions.locales', ['fr' => 'Francais']);
        config()->set('vtuts.locales', ['es' => 'Espanol']);
        config()->set('vdocs.locales', ['pt' => 'Portugues']);

        $this->assertSame([
            ['value' => 'en', 'label' => 'English'],
            ['value' => 'it', 'label' => 'Italiano'],
        ], $this->localeChoices());
    }

    public function test_it_falls_back_to_the_configured_default_locale(): void
    {
        app()->setLocale('it');

        config()->set('app.locales', null);
        config()->set('app.default_locale', 'en');
        config()->set('vtuts.locales', ['es' => 'Espanol']);

        $choices = $this->localeChoices();

        $this->assertCount(1, $choices);
        $this->assertSame('en', $choices[0]['value']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function localeChoices(): array
    {
        /** @var array{value: array{choices?: list<array{value: string, label: string}>}} $localeOption */
        $localeOption = Collection::make(EditorConditionHooks::options())
            ->firstWhere('key', 'locale');

        return $localeOption['value']['choices'] ?? [];
    }
}
