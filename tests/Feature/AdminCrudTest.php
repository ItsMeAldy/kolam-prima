<?php

namespace Tests\Feature;

use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
