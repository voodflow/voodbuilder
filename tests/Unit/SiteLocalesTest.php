<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SiteLocales;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteLocalesTest extends TestCase
{
    public function test_it_reads_a_comma_separated_env_list(): void
    {
        config()->set('app.locales', 'en, it');
        config()->set('app.default_locale', 'en');

        $this->assertSame(['en', 'it'], SiteLocales::codes());
        $this->assertSame(['it'], SiteLocales::nonDefaultCodes());
    }

    public function test_it_keeps_labels_from_a_code_label_map(): void
    {
        config()->set('app.locales', ['en' => 'English', 'it' => 'Italiano']);

        $this->assertSame(['en' => 'English', 'it' => 'Italiano'], SiteLocales::options());
    }

    public function test_default_ignores_the_runtime_visitor_locale(): void
    {
        config()->set('app.locales', ['en', 'it']);
        config()->set('app.default_locale', 'it');

        app()->setLocale('en');

        $this->assertSame('it', SiteLocales::default());
        $this->assertSame('it', VoodbuilderSettings::primaryLocale());
    }

    public function test_default_falls_back_to_first_locale_when_not_listed(): void
    {
        config()->set('app.locales', ['it', 'en']);
        config()->set('app.default_locale', 'fr');

        $this->assertSame('it', SiteLocales::default());
    }

    public function test_package_configs_are_not_a_language_source(): void
    {
        config()->set('app.locales', null);
        config()->set('app.default_locale', 'en');
        config()->set('vtuts.locales', ['es' => 'Espanol']);
        config()->set('vtuts.default_locale', 'es');

        $this->assertSame(['en'], SiteLocales::codes());
        $this->assertSame('en', SiteLocales::default());
    }
}
