<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Vpress\Support\ConfigureRoutesForVpress;
use Voodflow\Vpress\Tests\TestCase;

class ConfigureRoutesForVpressTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(base_path('routes/web.php'));

        parent::tearDown();
    }

    public function test_it_removes_the_default_laravel_welcome_route(): void
    {
        File::ensureDirectoryExists(base_path('routes'));

        File::put(base_path('routes/web.php'), <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
PHP);

        $this->assertTrue(ConfigureRoutesForVpress::apply());

        $contents = File::get(base_path('routes/web.php'));

        $this->assertStringNotContainsString("view('welcome')", $contents);
        $this->assertStringContainsString('voodflow/vpress', $contents);
    }
}
