<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorAssetUploadTest extends TestCase
{
    protected function tearDown(): void
    {
        EditorGate::authorizeUsing(null);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Filament::registerPanel(
            Panel::make()
                ->id('admin')
                ->path('admin'),
        );

        EditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesEditorBuilder() && auth()->check(),
        );

        Storage::fake('public');
    }

    public function test_authenticated_user_receives_relative_storage_url(): void
    {
        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }
        };

        $user->forceFill([
            'name' => 'Admin',
            'email' => 'admin-upload@example.com',
        ])->save();

        $this->actingAs($user);

        $response = $this->post(route('voodbuilder.editor.upload'), [
            'file' => UploadedFile::fake()->image('hero.jpg', 1200, 800),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $url = $response->json('data.0');

        $this->assertIsString($url);
        $this->assertStringStartsWith('/storage/voodbuilder/', $url);
        $this->assertStringNotContainsString('/grapesjs/', $url);
        $this->assertStringEndsWith('.jpg', $url);
        $this->assertStringNotContainsString('http://', $url);

        Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));
    }

    public function test_guest_cannot_upload_assets(): void
    {
        $this->post(route('voodbuilder.editor.upload'), [
            'file' => UploadedFile::fake()->image('hero.jpg'),
        ], [
            'Accept' => 'application/json',
        ])->assertUnauthorized();
    }
}
