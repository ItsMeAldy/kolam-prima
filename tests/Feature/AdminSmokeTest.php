<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render(): void
    {
        $user = User::factory()->create();

        foreach ([
            '/admin',
            '/admin/projects',
            '/admin/packages',
            '/admin/services',
            '/admin/videos',
            '/admin/manage-site-settings',
        ] as $path) {
            $this->actingAs($user)->get($path)->assertSuccessful();
        }
    }
}
