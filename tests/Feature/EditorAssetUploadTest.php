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
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Voodflow\Voodbuilder\Models\MediaLibrary;
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
            MediaLibraryServiceProvider::class,
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
        config([
            'voodbuilder.editor.upload.disk' => 'public',
            'media-library.disk_name' => 'public',
        ]);
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
        $this->assertStringStartsWith('/storage/', $url);
        $this->assertStringNotContainsString('/grapesjs/', $url);
        $this->assertStringEndsWith('.jpg', $url);
        $this->assertStringNotContainsString('http://', $url);

        $this->assertSame(1, MediaLibrary::current()->getMedia(MediaLibrary::COLLECTION_IMAGES)->count());
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));
    }

    public function test_authenticated_user_can_upload_mp4_video(): void
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
            'email' => 'admin-video-upload@example.com',
        ])->save();

        $this->actingAs($user);

        $response = $this->post(route('voodbuilder.editor.upload'), [
            'file' => UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $url = $response->json('data.0');

        $this->assertIsString($url);
        $this->assertStringStartsWith('/storage/', $url);
        $this->assertStringEndsWith('.mp4', $url);

        $this->assertSame(1, MediaLibrary::current()->getMedia(MediaLibrary::COLLECTION_VIDEOS)->count());
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));
    }

    public function test_authenticated_user_can_list_media_library(): void
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
            'email' => 'admin-media-library@example.com',
        ])->save();

        $this->actingAs($user);

        $library = MediaLibrary::current();
        $library->addMedia(UploadedFile::fake()->image('hero.jpg'))
            ->usingFileName('hero.jpg')
            ->toMediaCollection(MediaLibrary::COLLECTION_IMAGES);
        $library->addMedia(UploadedFile::fake()->create('reel.mp4', 1024, 'video/mp4'))
            ->usingFileName('reel.mp4')
            ->toMediaCollection(MediaLibrary::COLLECTION_VIDEOS);

        $response = $this->getJson(route('voodbuilder.editor.media.index'));

        $response->assertOk();
        $response->assertJsonPath('data.0.type', fn ($type) => in_array($type, ['image', 'video'], true));
        $this->assertCount(2, $response->json('data'));
        $this->assertTrue(collect($response->json('data'))->contains(
            fn (array $asset): bool => ($asset['type'] ?? null) === 'image' && str_contains((string) ($asset['name'] ?? ''), 'hero'),
        ));
        $this->assertTrue(collect($response->json('data'))->contains(
            fn (array $asset): bool => ($asset['type'] ?? null) === 'video' && str_contains((string) ($asset['name'] ?? ''), 'reel'),
        ));
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
