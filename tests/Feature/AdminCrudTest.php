<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Videos\Pages\CreateVideo;
use App\Filament\Resources\Videos\Pages\EditVideo;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        SiteSetting::current();
    }

    public function test_admin_can_create_and_edit_service(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateService::class)
            ->fillForm([
                'title' => 'Layanan Baru',
                'description' => 'Deskripsi layanan baru.',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $service = Service::query()->where('title', 'Layanan Baru')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(EditService::class, ['record' => $service->getKey()])
            ->fillForm(['title' => 'Layanan Diubah'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', ['title' => 'Layanan Diubah']);
    }

    public function test_admin_can_create_and_edit_package(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreatePackage::class)
            ->fillForm([
                'name' => 'Paket Baru',
                'tagline' => 'Tagline baru.',
                'features' => [
                    ['feature' => 'Fitur Satu'],
                    ['feature' => 'Fitur Dua'],
                ],
                'is_popular' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $package = Package::query()->where('name', 'Paket Baru')->firstOrFail();

        $this->assertSame(['Fitur Satu', 'Fitur Dua'], $package->features);
        $this->assertTrue($package->is_popular);

        Livewire::actingAs($this->admin)
            ->test(EditPackage::class, ['record' => $package->getKey()])
            ->fillForm(['name' => 'Paket Diubah'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('packages', ['name' => 'Paket Diubah']);
    }

    public function test_admin_can_create_and_edit_project(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateProject::class)
            ->fillForm([
                'title' => 'Proyek Baru',
                'slug' => 'proyek-baru',
                'location' => 'Bandung',
                'property_type' => 'villa',
                'sort_order' => 1,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('slug', 'proyek-baru')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['title' => 'Proyek Diubah'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', ['title' => 'Proyek Diubah']);
    }

    public function test_project_requires_unique_slug(): void
    {
        Project::create([
            'title' => 'Proyek Lama',
            'slug' => 'slug-sama',
            'location' => 'Bogor',
            'property_type' => 'rumah',
        ]);

        Livewire::actingAs($this->admin)
            ->test(CreateProject::class)
            ->fillForm([
                'title' => 'Proyek Lain',
                'slug' => 'slug-sama',
                'location' => 'Bali',
                'property_type' => 'hotel',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_admin_can_create_and_edit_video(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(CreateVideo::class)
            ->fillForm([
                'title' => 'Video Baru',
                'video_path' => [UploadedFile::fake()->create('uji.mp4', 128, 'video/mp4')],
                'sort_order' => 1,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $video = Video::query()->where('title', 'Video Baru')->firstOrFail();

        $this->assertNotNull($video->video_path);
        Storage::disk('public')->assertExists($video->video_path);

        Livewire::actingAs($this->admin)
            ->test(EditVideo::class, ['record' => $video->getKey()])
            ->fillForm(['title' => 'Video Diubah'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('videos', ['title' => 'Video Diubah']);
    }

    public function test_admin_can_edit_project_that_already_has_uploaded_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/projects/sudah-ada.jpg', 'gambar-lama');

        $project = Project::create([
            'title' => 'Proyek Berisi Gambar',
            'slug' => 'proyek-berisi-gambar',
            'location' => 'Bandung',
            'property_type' => 'villa',
        ]);

        $project->images()->create([
            'path' => 'media/projects/sudah-ada.jpg',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->assertOk()
            ->fillForm(['title' => 'Judul Diperbarui'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', ['title' => 'Judul Diperbarui']);
        $this->assertDatabaseHas('project_images', ['path' => 'media/projects/sudah-ada.jpg']);
    }

    public function test_admin_can_upload_service_icon_image(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(CreateService::class)
            ->fillForm([
                'title' => 'Layanan Bergambar',
                'description' => 'Deskripsi layanan bergambar.',
                'image_path' => [UploadedFile::fake()->image('ikon.png', 200, 200)],
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $service = Service::query()->where('title', 'Layanan Bergambar')->firstOrFail();

        $this->assertNotNull($service->image_path);
        Storage::disk('public')->assertExists($service->image_path);
    }

    public function test_admin_can_upload_hero_image_and_logo(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(ManageSiteSettings::class)
            ->fillForm([
                'brand_name' => 'Kolam Prima',
                'whatsapp_number' => '6281234567890',
                'hero_image' => [UploadedFile::fake()->image('hero.jpg', 1200, 900)],
                'logo_path' => [UploadedFile::fake()->image('logo.png', 256, 256)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current();

        $this->assertNotNull($settings->hero_image);
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->hero_image);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_admin_can_upload_project_image(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(CreateProject::class)
            ->fillForm([
                'title' => 'Proyek Bergambar',
                'slug' => 'proyek-bergambar',
                'location' => 'Bandung',
                'property_type' => 'villa',
                'images' => [
                    ['path' => [UploadedFile::fake()->image('kolam.jpg')], 'sort_order' => 1],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('slug', 'proyek-bergambar')->firstOrFail();
        $image = $project->images()->firstOrFail();

        $this->assertNotNull($image->path);
        Storage::disk('public')->assertExists($image->path);
    }
}
