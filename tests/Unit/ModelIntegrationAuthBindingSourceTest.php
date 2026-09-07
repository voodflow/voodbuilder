<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ModelIntegrationAuthBindingSource;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ModelIntegrationLatestBindingSource;
use Voodflow\Voodbuilder\Tests\TestCase;

class ModelIntegrationAuthBindingSourceTest extends TestCase
{
    public function test_supports_only_authenticatable_models(): void
    {
        $users = new ModelIntegration([
            'name' => 'Users',
            'model_class' => AuthUserRecord::class,
            'model_alias' => 'users',
            'fields' => ['essential' => ['name']],
        ]);

        $posts = new ModelIntegration([
            'name' => 'Posts',
            'model_class' => NonAuthRecord::class,
            'model_alias' => 'posts',
            'fields' => ['essential' => ['title']],
        ]);

        $this->assertTrue(ModelIntegrationAuthBindingSource::supports($users));
        $this->assertFalse(ModelIntegrationAuthBindingSource::supports($posts));
    }

    public function test_resolves_fields_from_authenticated_user_not_latest_record(): void
    {
        $integration = new ModelIntegration([
            'name' => 'Users',
            'model_class' => AuthUserRecord::class,
            'model_alias' => 'users',
            'fields' => ['essential' => ['name']],
        ]);

        AuthUserRecord::$latest = new AuthUserRecord([
            'id' => 99,
            'name' => 'Ultimo creato',
            'email' => 'latest@example.test',
            'password' => 'secret',
        ]);

        $loggedIn = new AuthUserRecord([
            'id' => 1,
            'name' => 'Paolo Loggato',
            'email' => 'paolo@example.test',
            'password' => 'secret',
        ]);

        Auth::login($loggedIn);

        $authSource = new ModelIntegrationAuthBindingSource($integration);
        $latestSource = new ModelIntegrationLatestBindingSource($integration);

        $this->assertSame('users.auth', $authSource->id());
        $this->assertSame('User profile', $authSource->label());
        $this->assertSame(
            'Paolo Loggato',
            $authSource->resolve('name', BindingContext::forPage(null)),
        );
        $this->assertSame(
            'Ultimo creato',
            $latestSource->resolve('name', BindingContext::forPage(null)),
        );
    }

    public function test_returns_null_when_guest(): void
    {
        $integration = new ModelIntegration([
            'name' => 'Users',
            'model_class' => AuthUserRecord::class,
            'model_alias' => 'users',
            'fields' => ['essential' => ['name']],
        ]);

        Auth::logout();

        $source = new ModelIntegrationAuthBindingSource($integration);

        $this->assertNull($source->resolve('name', BindingContext::forPage(null)));
    }
}

class AuthUserRecord extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    public static ?self $latest = null;

    protected $guarded = [];

    public $timestamps = false;

    public static function query(): AuthUserRecordQuery
    {
        return new AuthUserRecordQuery;
    }
}

class AuthUserRecordQuery
{
    public function publiclyListed(): self
    {
        return $this;
    }

    public function published(): self
    {
        return $this;
    }

    public function latest(string $column): self
    {
        return $this;
    }

    public function first(): ?AuthUserRecord
    {
        return AuthUserRecord::$latest;
    }
}

class NonAuthRecord extends Model
{
    protected $guarded = [];
}
