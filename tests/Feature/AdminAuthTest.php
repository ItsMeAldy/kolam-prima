<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::current();
    }

    public function test_guest_is_redirected_from_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')->assertSuccessful();
    }

    public function test_admin_user_can_access_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertSuccessful();
    }
}
